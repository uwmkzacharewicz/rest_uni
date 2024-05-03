<?php
namespace App\Service;

use App\Entity\User;
use App\Security\Role;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class UtilityService
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher)
    {
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
        $this->passwordHasher = $passwordHasher;
    }

    /** 
     * Generuje linki HATEOAS dla podanej encji
     */
    public function generateHateoasLinks($entity, array $linksConfig)
    {
        $links = [];
        foreach ($linksConfig as $linkName => $linkConfig) {
            $links[$linkName] = [
                'href' => $this->urlGenerator->generate($linkConfig['route'], [$linkConfig['param'] => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'method' => $linkConfig['method']
            ];
        }

        return $links;
    }

    public function serializeJson($data, array $groups = []): string
    {
        $context = SerializationContext::create()->setSerializeNull(true);
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        return $this->serializer->serialize($data, 'json', $context);
    }

    public function deserializeJson($data, string $type, array $groups = [])
    {
        $context = SerializationContext::create();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        return $this->serializer->deserialize($data, $type, 'json', $context);
    }

    public function hashPassword(User $user, string $password): string
    {
        return $this->passwordHasher->hashPassword($user, $password);
    }







}

?>