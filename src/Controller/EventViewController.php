<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventMilestone;
use App\Repository\EventMilestoneRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
class EventViewController extends AbstractController
{
    private EventRepository $eventRepository;
    private EventMilestoneRepository $milestoneRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EventRepository $eventRepository,
        EventMilestoneRepository $milestoneRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->eventRepository = $eventRepository;
        $this->milestoneRepository = $milestoneRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_event_list', methods: ['GET'])]
    public function listEvents(): Response
    {
        $events = $this->eventRepository->findAll();
        
        return $this->render('event/list.html.twig', [
            'events' => $events,
            'page_title' => 'Events List'
        ]);
    }

    #[Route('/view/{id}', name: 'app_event_view', methods: ['GET'])]
    public function viewEvent(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $milestones = $this->milestoneRepository->findByEventId($id);
        
        return $this->render('event/view.html.twig', [
            'event' => $event,
            'milestones' => $milestones,
            'page_title' => $event->getName()
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function newEvent(Request $request): Response
    {
        $event = new Event();
        
        if ($request->isMethod('POST')) {
            $event->setName($request->request->get('name'));
            $event->setUserId($request->request->get('userId'));
            $event->setStartDate(new \DateTime($request->request->get('startDate')));
            $event->setCategory($request->request->get('category'));
            
            $this->eventRepository->save($event, true);
            
            $this->addFlash('success', 'Event created successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
        }
        
        return $this->render('event/new.html.twig', [
            'page_title' => 'Create New Event'
        ]);
    }

    #[Route('/edit/{id}', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function editEvent(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        if ($request->isMethod('POST')) {
            $event->setName($request->request->get('name'));
            $event->setUserId($request->request->get('userId'));
            $event->setStartDate(new \DateTime($request->request->get('startDate')));
            $event->setCategory($request->request->get('category'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Event updated successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
        }
        
        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'page_title' => 'Edit Event: ' . $event->getName()
        ]);
    }

    #[Route('/delete/{id}', name: 'app_event_delete', methods: ['GET', 'POST'])]
    public function deleteEvent(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        if ($request->isMethod('POST')) {
            $this->eventRepository->remove($event, true);
            
            $this->addFlash('success', 'Event deleted successfully');
            return $this->redirectToRoute('app_event_list');
        }
        
        return $this->render('event/delete.html.twig', [
            'event' => $event,
            'page_title' => 'Delete Event: ' . $event->getName()
        ]);
    }

    #[Route('/{eventId}/milestones/new', name: 'app_milestone_new', methods: ['GET', 'POST'])]
    public function newMilestone(Request $request, int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $milestone = new EventMilestone();
        $milestone->setEvent($event);
        
        if ($request->isMethod('POST')) {
            $milestone->setName($request->request->get('name'));
            $milestone->setExpectedDate(new \DateTime($request->request->get('expectedDate')));
            
            if ($request->request->get('completionDate')) {
                $milestone->setCompletionDate(new \DateTime($request->request->get('completionDate')));
            }
            
            $milestone->setStatus($request->request->get('status'));
            
            $this->milestoneRepository->save($milestone, true);
            
            $this->addFlash('success', 'Milestone created successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $eventId]);
        }
        
        return $this->render('milestone/new.html.twig', [
            'event' => $event,
            'page_title' => 'Add Milestone to: ' . $event->getName()
        ]);
    }

    #[Route('/milestones/edit/{id}', name: 'app_milestone_edit', methods: ['GET', 'POST'])]
    public function editMilestone(Request $request, int $id): Response
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            throw $this->createNotFoundException('Milestone not found');
        }
        
        $event = $milestone->getEvent();
        
        if ($request->isMethod('POST')) {
            $milestone->setName($request->request->get('name'));
            $milestone->setExpectedDate(new \DateTime($request->request->get('expectedDate')));
            
            if ($request->request->get('completionDate')) {
                $milestone->setCompletionDate(new \DateTime($request->request->get('completionDate')));
            } else {
                $milestone->setCompletionDate(null);
            }
            
            $milestone->setStatus($request->request->get('status'));
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Milestone updated successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
        }
        
        return $this->render('milestone/edit.html.twig', [
            'milestone' => $milestone,
            'event' => $event,
            'page_title' => 'Edit Milestone: ' . $milestone->getName()
        ]);
    }

    #[Route('/milestones/delete/{id}', name: 'app_milestone_delete', methods: ['GET', 'POST'])]
    public function deleteMilestone(Request $request, int $id): Response
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            throw $this->createNotFoundException('Milestone not found');
        }
        
        $event = $milestone->getEvent();
        
        if ($request->isMethod('POST')) {
            $this->milestoneRepository->remove($milestone, true);
            
            $this->addFlash('success', 'Milestone deleted successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
        }
        
        return $this->render('milestone/delete.html.twig', [
            'milestone' => $milestone,
            'event' => $event,
            'page_title' => 'Delete Milestone: ' . $milestone->getName()
        ]);
    }

    #[Route('/milestones/complete/{id}', name: 'app_milestone_complete', methods: ['GET', 'POST'])]
    public function completeMilestone(int $id): Response
    {
        $milestone = $this->milestoneRepository->find($id);
        
        if (!$milestone) {
            throw $this->createNotFoundException('Milestone not found');
        }
        
        $event = $milestone->getEvent();
        
        $milestone->setStatus('Completed');
        $milestone->setCompletionDate(new \DateTime());
        
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Milestone marked as completed');
        return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
    }
} 