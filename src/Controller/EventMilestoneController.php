<?php

namespace App\Controller;

use App\Entity\EventMilestone;
use App\Repository\EventMilestoneRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/milestones')]
class EventMilestoneController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private EventMilestoneRepository $milestoneRepository;
    private EventRepository $eventRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventMilestoneRepository $milestoneRepository,
        EventRepository $eventRepository,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->milestoneRepository = $milestoneRepository;
        $this->eventRepository = $eventRepository;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_milestone_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $milestones = $this->milestoneRepository->findAll();
        
        return $this->json([
            'status' => 'success',
            'data' => $milestones,
        ]);
    }

    #[Route('/{id}', name: 'app_milestone_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            return $this->json([
                'status' => 'error',
                'message' => 'Milestone not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'status' => 'success',
            'data' => $milestone,
        ]);
    }

    #[Route('', name: 'app_milestone_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['eventId'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event ID is required',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $event = $this->eventRepository->find($data['eventId']);
        
        if (!$event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $milestone = new EventMilestone();
        $milestone->setEvent($event);
        $milestone->setName($data['name'] ?? '');
        
        if (isset($data['expectedDate'])) {
            $milestone->setExpectedDate(new \DateTime($data['expectedDate']));
        }
        
        if (isset($data['completionDate'])) {
            $milestone->setCompletionDate(new \DateTime($data['completionDate']));
        }
        
        if (isset($data['status']) && in_array($data['status'], ['Not_Started', 'Started', 'Completed', 'Delay'])) {
            $milestone->setStatus($data['status']);
        }
        
        $errors = $this->validator->validate($milestone);
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
        
        $this->milestoneRepository->save($milestone, true);
        
        return $this->json([
            'status' => 'success',
            'message' => 'Milestone created successfully',
            'data' => $milestone,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_milestone_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            return $this->json([
                'status' => 'error',
                'message' => 'Milestone not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['eventId'])) {
            $event = $this->eventRepository->find($data['eventId']);
            
            if (!$event) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], Response::HTTP_NOT_FOUND);
            }
            
            $milestone->setEvent($event);
        }
        
        if (isset($data['name'])) {
            $milestone->setName($data['name']);
        }
        
        if (isset($data['expectedDate'])) {
            $milestone->setExpectedDate(new \DateTime($data['expectedDate']));
        }
        
        if (isset($data['completionDate'])) {
            $milestone->setCompletionDate(new \DateTime($data['completionDate']));
        } elseif (array_key_exists('completionDate', $data) && $data['completionDate'] === null) {
            $milestone->setCompletionDate(null);
        }
        
        if (isset($data['status']) && in_array($data['status'], ['Not_Started', 'Started', 'Completed', 'Delay'])) {
            $milestone->setStatus($data['status']);
        }
        
        $errors = $this->validator->validate($milestone);
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
            'message' => 'Milestone updated successfully',
            'data' => $milestone,
        ]);
    }

    #[Route('/{id}', name: 'app_milestone_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            return $this->json([
                'status' => 'error',
                'message' => 'Milestone not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $this->milestoneRepository->remove($milestone, true);
        
        return $this->json([
            'status' => 'success',
            'message' => 'Milestone deleted successfully',
        ]);
    }

    #[Route('/event/{eventId}', name: 'app_milestone_by_event', methods: ['GET'])]
    public function findByEvent(int $eventId): JsonResponse
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        $milestones = $this->milestoneRepository->findByEventId($eventId);
        
        return $this->json([
            'status' => 'success',
            'data' => $milestones,
        ]);
    }

    #[Route('/status/{status}', name: 'app_milestone_by_status', methods: ['GET'])]
    public function findByStatus(string $status): JsonResponse
    {
        if (!in_array($status, ['Not_Started', 'Started', 'Completed', 'Delay'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid status value',
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $milestones = $this->milestoneRepository->findByStatus($status);
        
        return $this->json([
            'status' => 'success',
            'data' => $milestones,
        ]);
    }
}