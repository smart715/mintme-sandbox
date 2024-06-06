<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use App\Manager\ApiKeyManagerInterface;
use App\Security\Model\ApiKeyCredentials;
use App\Security\Model\OAuthCredentials;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\ApiKeyAuthenticator as ApiKeyAuthenticatorConstraint;
use InvalidArgumentException;
use OAuth2\IOAuth2Storage;
use OAuth2\OAuth2AuthenticateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Validator\Validation;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    private ApiKeyManagerInterface $keyManager;
    private OAuth2 $oauth;
    private TranslatorInterface $translator;

    public function __construct(
        ApiKeyManagerInterface $keyManager,
        IOAuth2Storage $oAuthStorage,
        TranslatorInterface $translator
    ) {
        $this->keyManager = $keyManager;
        $this->oauth = new OAuth2($oAuthStorage);
        $this->translator = $translator;
    }

    /** {@inheritdoc}
     *
     * @param string $providerKey
     */
    public function createToken(Request $request, $providerKey): PreAuthenticatedToken
    {
        $public = $request->headers->get('X-API-ID');
        $private = $request->headers->get('X-API-KEY');
        $privateRequired = $request->attributes->get('_private_key_required') ?? false;

        $credentials = null;

        $validator = Validation::createValidator();
        $constraint = new ApiKeyAuthenticatorConstraint([
            'length' => 64,
            'allowNull' => false,
        ]);

        $violations = [];

        if (null != $token = $this->oauth->getBearerToken($request, true)) {
            // check maybe it Oauth token
            $credentials = new OAuthCredentials($token);
        } elseif ($public) {
            $publicViolations = $validator->validate($public, $constraint);
            $constraint->allowNull = true;
            $privateViolations = $validator->validate($public, $constraint);

            array_push(
                $violations,
                ...$publicViolations,
                ...$privateViolations,
            );

            $credentials = new ApiKeyCredentials($public, $private, $privateRequired);
        }

        if (0 < count($violations) || null === $credentials) {
            throw new BadCredentialsException(
                $this->translator->trans('api.oauth.bad_credentials')
            );
        }

        return new PreAuthenticatedToken(
            'anon.',
            $credentials,
            $providerKey
        );
    }

    /** {@inheritdoc}
     *
     * @param string $providerKey
     */
    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /** {@inheritdoc}
     *
     * @param string $providerKey
     */
    public function authenticateToken(
        TokenInterface $token,
        UserProviderInterface $userProvider,
        $providerKey
    ): PreAuthenticatedToken {
        if (!$userProvider instanceof ApiKeyUserProvider) {
            throw new InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of ApiKeyUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $username = null;
        $credentials = $token->getCredentials();

        if ($credentials instanceof ApiKeyCredentials) {
            $this->assertApiKey($credentials);
            $username = $userProvider->getUsernameForApiKey($credentials->getPublic());
        } elseif ($credentials instanceof OAuthCredentials) {
            $this->assertToken($credentials);
            $username = $this->oauth->getUsernameForToken($credentials->getToken());
        }

        if (!$username) {
            throw new CustomUserMessageAuthenticationException($this->translator->trans(
                'api.oauth.invalid_credentials',
                [ 'token' => $credentials->getToken() ]
            ), [], Response::HTTP_BAD_REQUEST);
        }

        $user = $userProvider->loadUserByUsername($username);

        if (!$user) {
            throw new BadCredentialsException();
        }

        $roles = !in_array(User::ROLE_AUTHENTICATED, $user->getRoles(), true) ?
            [User::ROLE_API] :
            [User::ROLE_API, User::ROLE_AUTHENTICATED];

        return new PreAuthenticatedToken(
            $user,
            $credentials->getToken(),
            $providerKey,
            $roles
        );
    }

    /** {@inheritdoc} */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new Response(
            json_encode([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => $this->translator->trans('api.oauth.unauthorized'),
            ]),
            Response::HTTP_UNAUTHORIZED
        );
    }

    private function assertApiKey(ApiKeyCredentials $credentials): void
    {
        $apiKey = $this->keyManager->findApiKey($credentials->getPublic());

        if (is_null($apiKey)) {
            throw new BadCredentialsException('API Key not found.');
        }

        if ($credentials->isPrivateRequired()) {
            if (is_null($credentials->getPrivate())) {
                throw new BadCredentialsException('API private key is required.');
            }

            if (!password_verify($credentials->getPrivate(), $apiKey->getPrivateKey())) {
                throw new BadCredentialsException('API private key is invalid.');
            }
        }
    }

    private function assertToken(OAuthCredentials $credentials): void
    {
        try {
            $token = $credentials->getToken();
            $this->oauth->verifyAccessToken($token);
        } catch (OAuth2AuthenticateException $e) {
            throw new BadCredentialsException('Invalid token.');
        }
    }
}
