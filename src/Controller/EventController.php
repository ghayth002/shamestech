<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/events')]
class EventController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventRepository $eventRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->eventRepository = $eventRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_event_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $events = $this->eventRepository->findAll();
        
        return $this->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'status' => 'success',
            'data' => $event,
        ]);
    }

    #[Route('', name: 'app_event_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $event = new Event();
        $event->setName($data['name'] ?? '');
        $event->setUserId($data['userId'] ?? 0);
        
        if (isset($data['startDate'])) {
            $event->setStartDate(new \DateTime($data['startDate']));
        }
        
        $event->setCategory($data['category'] ?? null);
        
        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $this->eventRepository->save($event, true);
        
        return $this->json([
            'status' => 'success',
            'message' => 'Event created successfully',
            'data' => $event,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_event_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $event->setName($data['name']);
        }
        
        if (isset($data['userId'])) {
            $event->setUserId($data['userId']);
        }
        
        if (isset($data['startDate'])) {
            $event->setStartDate(new \DateTime($data['startDate']));
        }
        
        if (isset($data['category'])) {
            $event->setCategory($data['category']);
        }
        
        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'status' => 'success',
            'message' => 'Event updated successfully',
            'data' => $event,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $this->eventRepository->remove($event, true);
        
        return $this->json([
            'status' => 'success',
            'message' => 'Event deleted successfully',
        ]);
    }

    #[Route('/user/{userId}', name: 'app_event_by_user', methods: ['GET'])]
    public function findByUser(int $userId): JsonResponse
    {
        $events = $this->eventRepository->findByUserId($userId);
        
        return $this->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    #[Route('/category/{category}', name: 'app_event_by_category', methods: ['GET'])]
    public function findByCategory(string $category): JsonResponse
    {
        $events = $this->eventRepository->findByCategory($category);
        
        return $this->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }
} 