<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserEmailValidator extends ConstraintValidator
{
    /** @var mixed */
    public $user;

    /** @var UserManagerInterface */
    private $userManager;

    public function __construct(UserManagerInterface $userManager, TokenStorageInterface $token)
    {
        $this->user = $token->getToken()->getUser();
        $this->userManager = $userManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        $user = $this->userManager->findUserByEmail($value ?? '');

        if (!is_null($user) && ($this->user !== $user || $value === $user->getEmail())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        } elseif (true === $this->checkDisposable($value)) {
            $this->context->buildViolation('Invalid email domain')->addViolation();
        }
    }

    protected function checkDisposable($email): bool
    {
        $email = substr($email, strrpos($email, '@')+1);
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://open.kickbox.com/v1/disposable/'.$email);
        $response = json_decode($response->getContent(), true);

        return $response['disposable'];
    }
}
