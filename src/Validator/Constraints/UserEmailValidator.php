<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserEmailValidator extends ConstraintValidator
{
    /** @var mixed */
    private $user;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var mixed  */
    protected $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @var Security */
    private $security;

    public function __construct(
        UserManagerInterface $userManager,
        TokenStorageInterface $token,
        Security $security
    ) {
        $this->user = $token->getToken()->getUser();
        $this->userManager = $userManager;
        $this->security = $security;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if ($this->security->getUser()) {
            return;
        }

        $domain = $this->getEmailDomain($value);
        $name = $this->getEmailName($value);

        $user = $this->userManager->findUserByEmail($value ?? '');

        if (is_null($user) && in_array($domain, $this->gmailDomains)) {
            $user = $this->userManager->findUserByEmail($name.'@'.$this->gmailDomains[0]);

            if (is_null($user)) {
                $user = $this->userManager->findUserByEmail($name.'@'.$this->gmailDomains[1]);
            }
        }

        if (!is_null($user) && ($this->user !== $user || $value === $user->getEmail())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    /** @inheritdoc */
    protected function getEmailDomain($email): string
    {
        if (!$email) {
            return $email = '';
        }

        return substr($email, strrpos($email, '@') + 1) ?? '';
    }

    /** @inheritdoc */
    protected function getEmailName($email): string
    {
        if (!$email) {
            return $email = '';
        }

        return  strstr($email, '@', true) ?? '';
    }

}
