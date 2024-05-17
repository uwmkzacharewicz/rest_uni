<?php
namespace App\Service;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;

use App\Security\Role;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityService
{
    private $entityManager;
    private $propertyAccessor;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function findAll(string $entityClass): array
    {
        return $this->entityManager->getRepository($entityClass)->findAll();
    }

    public function find(string $entityClass, int $id)
    {
        return $this->entityManager->getRepository($entityClass)->find($id);
    }

    public function findEntityByFiled(string $entityClass, string $field, $value)
    {
        return $this->entityManager->getRepository($entityClass)->findOneBy([$field => $value]);
    }

    public function findEntitiesByField(string $entityClass, string $field, $value): array
    {
        return $this->entityManager->getRepository($entityClass)->findBy([$field => $value]);
    }

    public function setFieldValue($entity, string $field, $value)
    {
        // Sprawdzenie, czy można ustawić wartość dla danego pola
        if (!$this->propertyAccessor->isWritable($entity, $field)) {
            throw new \Exception("Właściwość {$property} nie jest zapisywalna lub nie istnieje w " . get_class($entity));            
        }
        // Ustawienie wartości
        $this->propertyAccessor->setValue($entity, $field, $value);
        // Zapisanie zmian w bazie danych
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function updateEntityWithFields($entity, array $data)
    {
        // Rozpoczęcie transakcji
        $this->entityManager->beginTransaction();  
        try 
        {
            foreach ($data as $property => $value) {
                if ($this->propertyAccessor->isWritable($entity, $property)) {
                    $this->propertyAccessor->setValue($entity, $property, $value);
                } else {
                    throw new \Exception("Właściwość {$property} nie jest zapisywalna lub nie istnieje w " . get_class($entity));
                }
            }

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $entity;

        } catch (UniqueConstraintViolationException $e) {
            // Obsługa specyficznych wyjątków związanych z naruszeniem ograniczeń
            $this->entityManager->rollback();
            throw new \Exception('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Ogólna obsługa wyjątków
            $this->entityManager->rollback();
            throw new \Exception('Application error: ' . $e->getMessage());
        }         
    }

    public function addEntity(string $entityClass, array $data)
    {
        $entity = new $entityClass();

        // Rozpocznij transakcję
        $this->entityManager->beginTransaction();

        try{
            foreach ($data as $property => $value) {
                // Sprawdź, czy encja ma setter dla danej właściwości
                if ($this->propertyAccessor->isWritable($entity, $property)) {
                    $this->propertyAccessor->setValue($entity, $property, $value);
                } else {
                    throw new \Exception("Właściwość {$property} nie jest zapisywalna lub nie istnieje w pliku" . $entityClass);
                }
            }

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $entity;

        } catch (UniqueConstraintViolationException $e) {
            // Obsługa specyficznych wyjątków związanych z naruszeniem ograniczeń
            $this->entityManager->rollback();
            throw new \Exception('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Ogólna obsługa wyjątków
            $this->entityManager->rollback();
            throw new \Exception('Application error: ' . $e->getMessage());
        }
    
    }


    public function updateEntity($entity)
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->entityManager->commit();
            return $entity;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new \Exception('Application error during entity update: ' . $e->getMessage());
        }
    }


    public function deleteEntiy($entity)
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new \Exception('Application error: ' . $e->getMessage());
        }
    }


}
?>