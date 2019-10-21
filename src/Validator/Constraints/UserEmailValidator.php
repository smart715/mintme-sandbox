<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\UserManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserEmailValidator extends ConstraintValidator
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

    /** @inheritdoc */
    protected function getEmailDomain($email): string
    {
        if (!substr($email ?? '', strrpos($email ?? '', '@') + 1) || !$email) {
            return '';
        }

        return substr($email, strrpos($email, '@') + 1);
    }

    /** @inheritdoc */
    protected function getEmailName($email): string
    {
        if (!strstr($email ?? '', '@', true) || !$email) {
            return '';
        }

        return str_replace('.', '', strstr($email, '@', true));
    }

    /**@param $email string|null
     * @return bool */
    protected function gmailHandler(?string $email): bool
    {
        $users = $this->userManager->getGmailUsers();

        if ($users) {
            foreach ($users as $user) {
                if ($this->getEmailName($user->getEmail())
                    ===
                    $this->getEmailName($email)) {
                    return true;
                }
            }
        }

        return false;
    }
}
