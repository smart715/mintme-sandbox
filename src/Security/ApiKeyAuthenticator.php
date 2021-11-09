<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\User;
use App\Manager\ApiKeyManagerInterface;
use App\Security\Model\ApiKeyCredentials;
use App\Security\Model\OAuthCredentials;
use InvalidArgumentException;
use OAuth2\IOAuth2Storage;
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

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    /** @var ApiKeyManagerInterface */
    private $keyManager;

    /** @var OAuth2 */
    private $oauth;

    public function __construct(ApiKeyManagerInterface $keyManager, IOAuth2Storage $oAuthStorage)
    {
        $this->keyManager = $keyManager;
        $this->oauth = new OAuth2($oAuthStorage);
    }

    /** {@inheritdoc}
     *
     * @param string $providerKey
     */
    public function createToken(Request $request, $providerKey): PreAuthenticatedToken
    {
        $public = $request->headers->get('X-API-ID');
        $private = $request->headers->get('X-API-KEY');

        $credentials = null;

        if (!(!$public ||
            !$private ||
            !is_string($public) ||
            !is_string($private) ||
            64 != strlen($public) ||
            64 != strlen($private)
        )) {
            $credentials = new ApiKeyCredentials($public, $private);
        } elseif (null != $token = $this->oauth->getBearerToken($request, true)) {
            // check maybe it Oauth token
            $credentials = new OAuthCredentials($token);
        } else {
            throw new BadCredentialsException();
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
            // this is Api Key authentication
            $key = $this->keyManager->findApiKey($credentials->getPublic());

            if (is_null($key) || !password_verify($credentials->getPrivate(), $key->getPrivateKey())) {
                throw new BadCredentialsException();
            }

            $username = $userProvider->getUsernameForApiKey($credentials->getPublic());
        } elseif ($credentials instanceof OAuthCredentials) {
            // this is OAuth authentication
            $this->oauth->verifyAccessToken($credentials->getToken());

            //if haven't exception then token is correct
            $username = $this->oauth->getUsernameForToken($credentials->getToken());
        }

        if (!$username) {
            throw new CustomUserMessageAuthenticationException(
                sprintf('API Key or Token "%s" does not exist.', $credentials->getToken()),
                [],
                400
            );
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
            strtr($exception->getMessageKey(), $exception->getMessageData()),
            401
        );
    }
}
