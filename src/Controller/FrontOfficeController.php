<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/front')]
class FrontOfficeController extends AbstractController
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->eventRepository = $eventRepository;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    #[Route('', name: 'app_front_office')]
    public function index(): Response
    {
        $events = $this->eventRepository->findAll();
        
        return $this->render('front/index.html.twig', [
            'events' => $events,
            'page_title' => 'Events Calendar'
        ]);
    }

    #[Route('/event/{id}', name: 'app_front_event')]
    public function viewEvent(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        return $this->render('front/event.html.twig', [
            'event' => $event,
            'page_title' => $event->getName()
        ]);
    }

    #[Route('/event/{id}/qr-code', name: 'app_event_qr_code')]
    public function generateQrCode(int $id): Response
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Create a JSON structure with the event details instead of a URL
        $eventDetails = [
            'name' => $event->getName(),
            'date' => $event->getStartDate()->format('Y-m-d'),
            'category' => $event->getCategory(),
            'description' => 'Event ID: ' . $event->getId(),
        ];
        
        // Convert to JSON
        $eventDataString = json_encode($eventDetails);
        
        // Set up the QR code renderer
        $renderer = new ImageRenderer(
            new RendererStyle(300, 3),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($eventDataString);
        
        return $this->render('front/qr_code.html.twig', [
            'event' => $event,
            'qr_code' => $qrCode,
            'event_data' => $eventDetails,
            'page_title' => 'QR Code for ' . $event->getName()
        ]);
    }

    #[Route('/events/list', name: 'app_front_events_list')]
    public function listEvents(): Response
    {
        $events = $this->eventRepository->findAll();
        
        return $this->render('front/events_list.html.twig', [
            'events' => $events,
            'page_title' => 'All Events'
        ]);
    }
} 