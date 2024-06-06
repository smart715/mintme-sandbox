<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Media\Media;
use App\Entity\News\Post;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class UploadContentNewsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:news:upload';

    /** @var EntityManagerInterface */
    private $em;

    /** @var string */
    private $pathToUploadDir;

    /** @var bool */
    private $isUpdate;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Upload content of news')
            ->addArgument('pass_to_dir', InputArgument::OPTIONAL, 'directory name?')
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                'Update?'
            )
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->isUpdate = (bool)$input->getOption('update');
        $inputPathToUploadDir = $input->getArgument('pass_to_dir');

        if (is_array($inputPathToUploadDir)) {
            $io->error('Directory name must be string');

            return 1;
        }

        $currentDirPath = getcwd();
        $this->pathToUploadDir = $inputPathToUploadDir ?: $currentDirPath . '/uploads_coin';

        if (!file_exists($this->pathToUploadDir)) {
            $io->error('Directory does not exist');

            return 1;
        }

        $this->em->getConnection()->beginTransaction();

        try {
            $jsonNewsСontents = json_decode(
                (string)file_get_contents($this->pathToUploadDir . '/news.json'),
                true
            );
            $jsonMediaСontents = json_decode(
                (string)file_get_contents($this->pathToUploadDir . '/media.json'),
                true
            );
            $jsonNewsMediaСontents = json_decode(
                (string)file_get_contents($this->pathToUploadDir . '/news_image.json'),
                true
            );

            if (!$jsonNewsСontents || !$jsonMediaСontents || !$jsonNewsMediaСontents) {
                $io->error('An error occurred while decoding the json file');
                $this->em->getConnection()->rollBack();

                return 1;
            }

            $io->progressStart(count($jsonNewsСontents));

            foreach ($jsonNewsСontents as $newsCoin) {
                $io->progressAdvance();
                $isPostAbsent = 0 === count($this->getPostRepository()->findBy(['slug' =>$newsCoin['slug']]));

                if ($isPostAbsent) {
                    $this->addPost($newsCoin, $jsonNewsMediaСontents, $jsonMediaСontents);
                } elseif ($this->isUpdate) {
                    $this->updatePost($newsCoin, $jsonNewsMediaСontents, $jsonMediaСontents);
                }
            }
            
            $this->em->getConnection()->commit();
            $io->progressFinish();
            $io->success('Content uploaded');
        } catch (Throwable $exception) {
            $this->em->getConnection()->rollBack();
            $io->progressFinish();
            $io->error('An error occurred');

            return 1;
        }

        return 0;
    }

    private function addPost(array $newsCoin, array $jsonNewsMediaСontents, array $jsonMediaСontents): bool
    {
        $post = new Post();
        $post = $this->setData($newsCoin, $jsonNewsMediaСontents, $jsonMediaСontents, $post);
        $this->em->persist($post);
        $this->em->flush();
        
        return true;
    }

    private function updatePost(array $newsCoin, array $jsonNewsMediaСontents, array $jsonMediaСontents): bool
    {
        /** @var ?Post $post */
        $post = $this->getPostRepository()->findOneBy(['slug' =>$newsCoin['slug']]);

        if (!$post) {
            return false;
        }

        $this->setData($newsCoin, $jsonNewsMediaСontents, $jsonMediaСontents, $post);
        $this->em->flush();
        
        return true;
    }

    private function setData(array $newsCoin, array $jsonNewsMediaСontents, array $jsonMediaСontents, Post $post): Post
    {
        $post->setTitle($newsCoin['title']);
        $post->setAbstract($newsCoin['abstract']);
        $post->setContent($newsCoin['content']);
        $post->setRawContent($newsCoin['raw_content']);
        $post->setContentFormatter($newsCoin['content_formatter']);
        $post->setEnabled($newsCoin['enabled']);
        $post->setSlug($newsCoin['slug']);
        $post->setPublicationDateStart(
            !$newsCoin['publication_date_start']
                ? null
                : new \DateTime($newsCoin['publication_date_start'])
        );
        $post->setCommentsEnabled($newsCoin['comments_enabled']);
        $post->setCommentsCloseAt(
            !$newsCoin['comments_close_at']
                ? null
                : new \DateTime($newsCoin['comments_close_at'])
        );
        $post->setCommentsDefaultStatus($newsCoin['comments_default_status']);
        $post->setCommentsCount($newsCoin['comments_count']);
        $post->setCreatedAt(
            !$newsCoin['created_at']
                ? null
                : new \DateTime($newsCoin['created_at'])
        );
        $post->setUpdatedAt(
            !$newsCoin['updated_at']
                ? null
                : new \DateTime($newsCoin['updated_at'])
        );
        /** @var ?Media $media */
        $media = $this->storeMedia($jsonNewsMediaСontents, $jsonMediaСontents, $newsCoin['id']);

        if ($media) {
            $post->setImage($media);
        }
        
        return $post;
    }

    private function storeMedia(array $jsonNewsMediaСontents, array $jsonMediaСontents, int $newsCoinId): ?Media
    {
        $media = null;

        foreach ($jsonNewsMediaСontents as $newsMedia) {
            if ((int)$newsMedia['id'] === $newsCoinId) {
                foreach ($jsonMediaСontents as $mediaCoin) {
                    if ((int)$newsMedia['image_id'] === $mediaCoin['id']) {
                        $media = new Media();
                        $media->setName($mediaCoin['name']);
                        $media->setBinaryContent($this->pathToUploadDir . '/' . $mediaCoin['provider_reference']);
                        $media->setContext($mediaCoin['context']);
                        $media->setProviderName($mediaCoin['provider_name']);
                        $media->setEnabled($mediaCoin['enabled']);
                        $this->em->persist($media);
                        $this->em->flush();

                        break;
                    }
                }

                break;
            }
        }

        return $media;
    }

    private function getPostRepository(): EntityRepository
    {
        return $this->em->getRepository(Post::class);
    }
}
