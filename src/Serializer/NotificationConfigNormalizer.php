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

        $userNotificationConfig = $this->normalizer->normalize($object, 'array', $context);
        //dd($userNotificationConfig);
        $result = [];

        /*if ( $object->getType()  ) {

        }*/
        $notificationTypes = NotificationTypes::getAll();
        $notificationChannels = NotificationChannels::getAll();

            //dd($notificationTypes[]);
        foreach ($notificationTypes as $nType) {
            $userNotificationConfig[$nType] = true;
            /*$keyExist = array_key_exists($nType, $result);

            if ($keyExist) {
                continue;
            }

            $result[$nType] = [];*/



          /*  foreach ($notificationChannels as $nChannel) {
                $keyExist = array_key_exists($nType, $result);
                if (!$keyExist) {
                    $result[$nType] = [
                        $nChannel => $nChannel === $object->getChannel()
                    ];
                    //dd($result);
                }*/


           /// }





    //        foreach ($notificationChannels as $nChannel) {
                /*r$esult[$nType] = [

                ]*/
               // $result[$nType][$nChannel] = true;
                /*if ($object->getType() === $nType) {
                    $result['type'] = $object->getType();
                    $result[$object->getChannel()] = [
                        'text' => $object->getChannel(),
                        'value' => true,
                    ];
                }*/
           }
       // }

        return $userNotificationConfig;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof UserNotificationConfig;
    }
}
