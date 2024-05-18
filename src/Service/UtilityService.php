<?php
namespace App\Service;

use App\Entity\User;
use App\Security\Role;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function generateHateoasLinks($entity, array $linksConfig): array
{
    $links = [];
    foreach ($linksConfig as $linkName => $linkConfig) {
        if (isset($linkConfig['route'])) {
            $params = [];
            if (isset($linkConfig['param'])) {
                $paramValue = $linkConfig['value'] ?? $entity->getId();
                $params[$linkConfig['param']] = $paramValue;
            }
            $link = [
                'href' => $this->urlGenerator->generate($linkConfig['route'], $params, UrlGeneratorInterface::ABSOLUTE_URL),
                'method' => $linkConfig['method']
            ];
            if (isset($linkConfig['body'])) {
                $link['body'] = $linkConfig['body'];
            }
            $links[$linkName] = $link;
        } elseif (is_array($linkConfig)) {
            $links[$linkName] = $this->generateHateoasLinks($entity, $linkConfig);
        }
    }

    return $links;
}

    public function validateAndDecodeJson(Request $request, array $requiredFields): array
    {
        // Dekodowanie JSON
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Sprawdzenie, czy przekazano wszystkie wymagane dane
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Nie przekazano wymaganej danej: {$field}");
            }
        }

        return $data;
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

    public function createErrorResponse(string $status, string $error, int $code): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'error' => $error,
            'code' => $code
        ], $code);
    }

    public function createSuccessResponse(string $status, array $data = [], int $code = Response::HTTP_OK): Response
    {
        $response = [
            'status' => $status
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        $jsonContent = $this->serializeJson($response);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }







}

?>
