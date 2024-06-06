<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Blacklist\Blacklist;
use App\Entity\PhoneNumber;
use App\Manager\BlacklistManagerInterface;
use App\Manager\PhoneNumberManagerInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/* Cron job added to DB. */
class UpdateDisposablePhoneNumbers extends Command
{
    
    private LoggerInterface $logger;
    
    private LockFactory $lockFactory;
    
    private ParameterBagInterface $bag;
    
    private BlacklistManagerInterface $blacklistManager;
    
    private EntityManagerInterface $em;
    
    private PhoneNumberManagerInterface $phoneNumberManager;
    
    private PhoneNumberUtil $phoneUtil;

    public function __construct(
        LoggerInterface $logger,
        LockFactory $lockFactory,
        ParameterBagInterface $bag,
        BlacklistManagerInterface $blacklistManager,
        EntityManagerInterface $em,
        PhoneNumberManagerInterface $phoneNumberManager,
        PhoneNumberUtil $phoneUtil
    ) {
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
        $this->bag = $bag;
        $this->blacklistManager = $blacklistManager;
        $this->em = $em;
        $this->phoneNumberManager = $phoneNumberManager;
        $this->phoneUtil = $phoneUtil;
        
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this->setName('app:synchronize-phones')
            ->setDescription('Synchronize phone number list');
    }
    
    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->lockFactory->createLock('synchronize-phones');
        
        if (!$lock->acquire()) {
            return 0;
        }
        
        $io = new SymfonyStyle($input, $output);
        
        $this->logger->info('[blacklist] Update job started..');
        $this->logger->info('[blacklist] Phones from list fetch start..');
        
        $phoneListFetched = json_decode(file_get_contents(
            $this->bag->get('origin_to_disposable_phone_numbers'),
            true
        )?: '[]', true);
    
        if (!$phoneListFetched) {
            $io->error('Error fetching the phone list');
            
            return 1;
        }

        $this->logger->info('[blacklist] Phone numbers from list fetched..');

        try {
            $io->progressStart(count($phoneListFetched));

            $indexedBlackList = $this->indexByValue($this->blacklistManager->getList(Blacklist::PHONE));
            $indexedPhones = $this->indexByPhone($this->phoneNumberManager->findAllVerified());

            foreach (array_keys($phoneListFetched) as $number) {
                if (!isset($indexedBlackList[$number])) {
                    $this->blacklistManager->add((string)$number, Blacklist::PHONE, false);
                }

                $this->revoke((string)$number, $indexedPhones);

                $io->progressAdvance(1);
            }

            $this->em->flush();
            $io->progressFinish();

            $io->success('Synchronization completed');
            $this->logger->info('[blacklist] Update job finished..');
        } catch (\Throwable $ex) {
            $message = 'Something went wrong while syncing ' . $ex->getMessage();
            $this->logger->error('[blacklist] ' . $message);
            $io->error($message);
        } finally {
            $lock->release();
        }
    
        return 0;
    }
    
    /** @param array<Blacklist> $list */
    private function indexByValue(array $list): array
    {
        $indexed = [];

        foreach ($list as $item) {
            $indexed[$item->getValue()] = $item;
        }
        
        return $indexed;
    }

    /** @param array<PhoneNumber> $list */
    private function indexByPhone(array $list): array
    {
        $indexed = [];

        foreach ($list as $item) {
            $phoneNumber = $item->getPhoneNumber();
            $indexed[$this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164)] = $item;
        }
        
        return $indexed;
    }
    
    /**
     * @param PhoneNumber[] $list
     * @throws NumberParseException
     */
    private function revoke(string $phoneNumber, array $list): void
    {
        $phone = '+'.$phoneNumber;
        
        $verifiedPhoneNumber = $list[$phone] ?? null;
        
        if ($verifiedPhoneNumber) {
            $userProfile = $verifiedPhoneNumber->getProfile();
            $userProfile->setPhoneNumber(null);
            $this->em->remove($verifiedPhoneNumber);
        }
    }
}
