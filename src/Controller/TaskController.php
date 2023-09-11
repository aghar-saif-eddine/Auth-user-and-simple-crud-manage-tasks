<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;


class TaskController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/list', name: 'list_task', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Retrieve tasks from the database (you may need to implement Task entity and repository)
        $tasks = $this->entityManager->getRepository(Task::class)->findAll();

        // Serialize the tasks to JSON
        $data = $this->serializer->normalize($tasks);

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/task/{id}', name: 'get_task', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $task = $this->entityManager->getRepository(Task::class)->findOneBy(['id' => $id]);

        $data = [
            'id' => $task->getId(),
            'Name' => $task->getName(),
            'Description' => $task->getDescription(),
            'Date' => $task->getCreateAt(),

        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/create-task', name: 'create_task', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Deserialize JSON data into a Task object
        $task = $this->serializer->deserialize($request->getContent(), Task::class, 'json');

        // Validate and save the task to the database
        $entityManager = $this->entityManager;
        $entityManager->persist($task);
        $entityManager->flush();

        // Serialize the created task to JSON
        $data = $this->serializer->normalize($task);

        return new JsonResponse($data, Response::HTTP_CREATED);
    }

    #[Route('/show-task/{id}', name: 'task_show', methods: ['GET'])]
    public function show($id): Response
    {
        // Serialize the task to JSON
        $task = $this->entityManager->getRepository(Task::class)->findOneBy(['id' => $id]);
        $data = $this->serializer->normalize($task);
        return new JsonResponse($data);
    }

    #[Route('/edit-task/{id}', name: 'edit_task', methods: ['PUT'])]
    public function update(Request $request, Task $task): JsonResponse
    {
        // Deserialize JSON data into the existing Task object
        $updatedTask = $this->serializer->deserialize($request->getContent(), Task::class, 'json');

        // Update the task's properties
        $task->setName($updatedTask->getName());
        $task->setDescription($updatedTask->getDescription());

        // Validate and save the updated task to the database
        $entityManager = $this->entityManager;
        $entityManager->persist($task);
        $entityManager->flush();

        // Serialize the updated task to JSON
        $data = $this->serializer->normalize($task);
        return new JsonResponse($data);
    }

    #[Route('/delete-task/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        // Remove the task from the database
        $entityManager = $this->entityManager;
        $entityManager->remove($task);
        $entityManager->flush();
        return new JsonResponse(['status' => '200', 'message' => 'Task deleted successfully']);
    }

}
