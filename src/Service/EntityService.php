<?php
namespace App\Service;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EntityService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function generateHateoasLinks($entity, array $linksConfig, UrlGeneratorInterface $urlGenerator)
{
    $links = [];
    foreach ($linksConfig as $linkName => $linkConfig) {
        $links[$linkName] = [
            'href' => $urlGenerator->generate($linkConfig['route'], [$linkConfig['param'] => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'method' => $linkConfig['method']
        ];
    }
    return $links;
}


    /******************************** USER ********************************/

    /**
     * @return User[]
     */
    public function findAllUsers(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }

    /**
     * @return User|null
     */
    public function findUser(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    /******************************** STUDENT ********************************/

    /**
    * @return Student[]
    */
    public function findAllStudents(): array
    {
        return $this->entityManager->getRepository(Student::class)->findAll();
    }

    /**
     * @return Student|null
     */
    public function findStudent(int $id): ?Student
    {
        return $this->entityManager->getRepository(Student::class)->find($id);
    }


    /******************************** TEACHER ********************************/

    /**
     * @return Teacher[]
     */
    public function findAllTeachers(): array
    {
        return $this->entityManager->getRepository(Teacher::class)->findAll();
    }

    /**
     * @return Teacher|null
     */
    public function findTeacher(int $id): ?Teacher
    {
        return $this->entityManager->getRepository(Teacher::class)->find($id);
    }


    /******************************** COURSE ********************************/

    /**
     * @return Course[]
     */
    public function findAllCourses(): array
    {
        return $this->entityManager->getRepository(Course::class)->findAll();
    }

    /**
     * @return Course|null
     */
    public function findCourse(int $id): ?Course
    {
        return $this->entityManager->getRepository(Course::class)->find($id);
    }


    /******************************** ENROLLMENT ********************************/

    /**
     * @return Enrollment[]
     */
    public function findAllEnrollments(): array
    {
        return $this->entityManager->getRepository(Enrollment::class)->findAll();
    }

    /**
     * @return Enrollment|null
     */
    public function findEnrollment(int $id): ?Enrollment
    {
        return $this->entityManager->getRepository(Enrollment::class)->find($id);
    }


    

    // $linksConfig = [
    //     'self' => [
    //         'route' => 'api_students_id',
    //         'param' => 'id',
    //         'method' => 'GET'
    //     ],
    //     'login' => [
    //         'route' => 'api_students_login',
    //         'param' => 'id',
    //         'method' => 'GET'
    //     ]
    // ];
    
    // foreach ($students as $student) {
    //     $studentData = $student->toArray();
    //     $studentData['_links'] = $this->generateHateoasLinks($student, $linksConfig, $this->urlGenerator);
    //     $data[] = $studentData;
    // }

    // $linksConfig = [
    //     'self' => [
    //         'route' => 'api_students_id',
    //         'param' => 'id',
    //         'method' => 'GET'
    //     ],
    //     'login' => [
    //         'route' => 'api_students_login',
    //         'param' => 'id',
    //         'method' => 'GET'
    //     ]
    // ];
    
    // foreach ($students as $student) {
    //     $studentData = $student->toArray();
    //     $studentData['_links'] = $this->generateHateoasLinks($student, $linksConfig, $this->urlGenerator);
    //     $data[] = $studentData;
    // }


}
?>