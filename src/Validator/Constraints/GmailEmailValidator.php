<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\UserManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class GmailEmailValidator extends ConstraintValidator
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var mixed  */
    protected $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @var Security */
    private $security;

    public function __construct(
        Security $security,
        UserManagerInterface $userManager
    ) {
        $this->security = $security;
        $this->userManager = $userManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if ($this->security->getUser()) {
            return;
        }

        if ($this->gmailHandler($value) && in_array($this->getEmailDomain($value), $this->gmailDomains)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    /**
     * @param $email string|null
     * @return string
     */
    protected function getEmailDomain(?string $email): string
    {
        if (!$email || !substr($email, strrpos($email, '@') + 1)) {
            return '';
        }

        return substr($email, strrpos($email, '@') + 1);
    }

    /**
     * @param $email string|null
     * @return string
     */
    protected function getEmailName(?string $email): string
    {
        if (!$email || !strstr($email, '@', true)) {
            return '';
        }

        return str_replace('.', '', strstr($email, '@', true));
    }

    /**
     * @param $email string|null
     * @return bool
     */
    protected function gmailHandler(?string $email): bool
    {
        $users = $this->userManager->getUsersByDomains($this->gmailDomains);

        if ($users) {
            foreach ($users as $user) {
                if ($this->getEmailName($user->getEmail()) === $this->getEmailName($email)) {
                    return true;
                }
            }
        }

        return false;
    }
}
