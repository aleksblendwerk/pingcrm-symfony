<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Util\ImageHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

class ResizeUploadedImageSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [Events::POST_UPLOAD => 'resizeUploadedImage'];
    }

    public function resizeUploadedImage(Event $event): void
    {
        if ($event->getMapping()->getMappingName() !== 'user_photo') {
            return;
        }

        /** @var User $user */
        $user = $event->getObject();

        $pathToImage = sprintf(
            '%s/%s',
            rtrim($event->getMapping()->getUploadDestination(), DIRECTORY_SEPARATOR),
            $user->getPhotoFilename()
        );

        ImageHandler::createResizedImageFile($pathToImage, 40, 40);
        ImageHandler::createResizedImageFile($pathToImage, 60, 60);
    }
}
