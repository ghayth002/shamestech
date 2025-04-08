<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/front')]
class FrontOfficeController extends AbstractController
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->eventRepository = $eventRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'front_event_list', methods: ['GET'])]
    public function listEvents(): Response
    {
        $events = $this->eventRepository->findAll();
        
        return $this->render('front/event_list.html.twig', [
            'events' => $events,
            'page_title' => 'Events'
        ]);
    }

    #[Route('/events/view/{id}', name: 'front_event_view', methods: ['GET'])]
    public function viewEvent(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        return $this->render('front/event_view.html.twig', [
            'event' => $event,
            'page_title' => $event->getName()
        ]);
    }

    #[Route('/events/new', name: 'front_event_new', methods: ['GET', 'POST'])]
    public function newEvent(Request $request): Response
    {
        $event = new Event();
        
        if ($request->isMethod('POST')) {
            $event->setName($request->request->get('name'));
            // For frontoffice, we could use a logged-in user's ID or a default value
            $event->setUserId($this->getUser() ? $this->getUser()->getId() : 1); 
            $event->setStartDate(new \DateTime($request->request->get('startDate')));
            $event->setCategory($request->request->get('category'));
            
            $this->eventRepository->save($event, true);
            
            $this->addFlash('success', 'Event created successfully');
            return $this->redirectToRoute('front_event_list');
        }
        
        return $this->render('front/event_new.html.twig', [
            'page_title' => 'Create New Event'
        ]);
    }
} 