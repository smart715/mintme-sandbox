<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\UserNotificationConfig;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class NotificationConfigNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private ObjectNormalizer $normalizer;

    public function __construct(
        ObjectNormalizer $objectNormalizer
    ) {
        $this->normalizer = $objectNormalizer;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        /**
         * @var array $userNotificationConfig
         */
        $userNotificationConfig = $this->normalizer->normalize($object, $format, $context);
        $result = [];
        /*if ( $object->getType()  ) {

        }*/
        $notificationTypes = NotificationTypes::getAll();
        $notificationChannels = NotificationChannels::getAll();

        foreach ($notificationTypes as $nType) {
            foreach ($notificationChannels as $item) {
                // todo normalize the object
                
            }

        }
        $result['type'] = $object->getType();
        $result[$object->getChannel()] = [
            'text' => $object->getChannel(),
            'value' => true,
        ];



        return $result;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof UserNotificationConfig;
    }
}
