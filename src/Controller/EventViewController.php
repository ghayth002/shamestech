<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventMilestone;
use App\Repository\EventMilestoneRepository;
use App\Repository\EventRepository;
use App\Service\BadWordFilterService;
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
    private BadWordFilterService $badWordFilter;

    public function __construct(
        EventRepository $eventRepository,
        EventMilestoneRepository $milestoneRepository,
        EntityManagerInterface $entityManager,
        BadWordFilterService $badWordFilter
    ) {
        $this->eventRepository = $eventRepository;
        $this->milestoneRepository = $milestoneRepository;
        $this->entityManager = $entityManager;
        $this->badWordFilter = $badWordFilter;
    }

    #[Route('', name: 'app_event_list', methods: ['GET'])]
    public function listEvents(Request $request): Response
    {
        // Get filter parameters
        $category = $request->query->get('category');
        $dateRange = $request->query->get('daterange');
        $search = $request->query->get('search');
        
        // Base query
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('e')
            ->from(Event::class, 'e');
        
        // Apply category filter
        if ($category) {
            $queryBuilder->andWhere('e.category = :category')
                ->setParameter('category', $category);
        }
        
        // Apply date range filter
        if ($dateRange) {
            $now = new \DateTime();
            
            switch ($dateRange) {
                case 'this-month':
                    $startOfMonth = new \DateTime('first day of this month midnight');
                    $endOfMonth = new \DateTime('last day of this month 23:59:59');
                    $queryBuilder->andWhere('e.startDate BETWEEN :start AND :end')
                        ->setParameter('start', $startOfMonth)
                        ->setParameter('end', $endOfMonth);
                    break;
                
                case 'last-month':
                    $startOfLastMonth = new \DateTime('first day of last month midnight');
                    $endOfLastMonth = new \DateTime('last day of last month 23:59:59');
                    $queryBuilder->andWhere('e.startDate BETWEEN :start AND :end')
                        ->setParameter('start', $startOfLastMonth)
                        ->setParameter('end', $endOfLastMonth);
                    break;
                
                case 'this-year':
                    $startOfYear = new \DateTime(date('Y-01-01 00:00:00'));
                    $endOfYear = new \DateTime(date('Y-12-31 23:59:59'));
                    $queryBuilder->andWhere('e.startDate BETWEEN :start AND :end')
                        ->setParameter('start', $startOfYear)
                        ->setParameter('end', $endOfYear);
                    break;
                
                case 'future':
                    $queryBuilder->andWhere('e.startDate >= :now')
                        ->setParameter('now', $now);
                    break;
            }
        }
        
        // Apply search
        if ($search) {
            $queryBuilder->andWhere('e.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Execute query
        $events = $queryBuilder->getQuery()->getResult();
        
        // Get statistics
        $statistics = $this->eventRepository->getStatistics();
        
        return $this->render('event/list.html.twig', [
            'events' => $events,
            'statistics' => $statistics,
            'page_title' => 'Events List',
            'filters' => [
                'category' => $category,
                'daterange' => $dateRange,
                'search' => $search
            ]
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
        $error = null;
        $filteredContent = null;
        $errors = [];
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $userId = $request->request->get('userId');
            $startDate = $request->request->get('startDate');
            $category = $request->request->get('category');
            
            // Validation
            if (empty($name)) {
                $errors['name'] = 'Event name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Event name must be at least 3 characters';
            } elseif (strlen($name) > 100) {
                $errors['name'] = 'Event name cannot exceed 100 characters';
            }
            
            if (empty($userId)) {
                $errors['userId'] = 'User ID is required';
            } elseif (!is_numeric($userId) || $userId <= 0) {
                $errors['userId'] = 'User ID must be a positive number';
            }
            
            if (empty($startDate)) {
                $errors['startDate'] = 'Start date is required';
            } else {
                try {
                    new \DateTime($startDate);
                } catch (\Exception $e) {
                    $errors['startDate'] = 'Invalid date format';
                }
            }
            
            if (!empty($category) && strlen($category) > 50) {
                $errors['category'] = 'Category cannot exceed 50 characters';
            }
            
            // Check for bad words only if there are no validation errors
            if (empty($errors)) {
                // Check for bad words in the name and category
                $nameCheck = $this->badWordFilter->containsBadWords($name);
                $categoryCheck = $category ? $this->badWordFilter->containsBadWords($category) : ['containsBadWords' => false];
                
                if ($nameCheck['containsBadWords'] || $categoryCheck['containsBadWords']) {
                    $error = 'Your submission contains inappropriate content and cannot be accepted.';
                    $filteredContent = [];
                    
                    // Add specific details about what was flagged
                    if ($nameCheck['containsBadWords']) {
                        $error .= ' Please revise the event name.';
                        $filteredContent['name'] = [
                            'original' => $name,
                            'filtered' => $nameCheck['filtered']
                        ];
                    }
                    if ($categoryCheck['containsBadWords']) {
                        $error .= ' Please revise the category.';
                        $filteredContent['category'] = [
                            'original' => $category,
                            'filtered' => $categoryCheck['filtered']
                        ];
                    }
                    
                    $this->addFlash('danger', $error);
                } else {
                    // No bad words detected and validation passed, proceed with saving
                    $event->setName($name);
                    $event->setUserId($userId);
                    $event->setStartDate(new \DateTime($startDate));
                    $event->setCategory($category);
            
            $this->eventRepository->save($event, true);
            
            $this->addFlash('success', 'Event created successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
                }
            }
        }
        
        return $this->render('event/new.html.twig', [
            'page_title' => 'Create New Event',
            'error' => $error,
            'filteredContent' => $filteredContent,
            'errors' => $errors,
            'event' => $event
        ]);
    }

    #[Route('/edit/{id}', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function editEvent(Request $request, int $id): Response
    {
        $event = $this->eventRepository->find($id);
        $error = null;
        $filteredContent = null;
        $errors = [];
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $userId = $request->request->get('userId');
            $startDate = $request->request->get('startDate');
            $category = $request->request->get('category');
            
            // Validation
            if (empty($name)) {
                $errors['name'] = 'Event name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Event name must be at least 3 characters';
            } elseif (strlen($name) > 100) {
                $errors['name'] = 'Event name cannot exceed 100 characters';
            }
            
            if (empty($userId)) {
                $errors['userId'] = 'User ID is required';
            } elseif (!is_numeric($userId) || $userId <= 0) {
                $errors['userId'] = 'User ID must be a positive number';
            }
            
            if (empty($startDate)) {
                $errors['startDate'] = 'Start date is required';
            } else {
                try {
                    new \DateTime($startDate);
                } catch (\Exception $e) {
                    $errors['startDate'] = 'Invalid date format';
                }
            }
            
            if (!empty($category) && strlen($category) > 50) {
                $errors['category'] = 'Category cannot exceed 50 characters';
            }
            
            // Check for bad words only if there are no validation errors
            if (empty($errors)) {
                // Check for bad words in the name and category
                $nameCheck = $this->badWordFilter->containsBadWords($name);
                $categoryCheck = $category ? $this->badWordFilter->containsBadWords($category) : ['containsBadWords' => false];
                
                if ($nameCheck['containsBadWords'] || $categoryCheck['containsBadWords']) {
                    $error = 'Your submission contains inappropriate content and cannot be accepted.';
                    $filteredContent = [];
                    
                    // Add specific details about what was flagged
                    if ($nameCheck['containsBadWords']) {
                        $error .= ' Please revise the event name.';
                        $filteredContent['name'] = [
                            'original' => $name,
                            'filtered' => $nameCheck['filtered']
                        ];
                    }
                    if ($categoryCheck['containsBadWords']) {
                        $error .= ' Please revise the category.';
                        $filteredContent['category'] = [
                            'original' => $category,
                            'filtered' => $categoryCheck['filtered']
                        ];
                    }
                    
                    $this->addFlash('danger', $error);
                } else {
                    // No bad words detected and validation passed, proceed with saving
                    $event->setName($name);
                    $event->setUserId($userId);
                    $event->setStartDate(new \DateTime($startDate));
                    $event->setCategory($category);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Event updated successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
                }
            }
        }
        
        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'page_title' => 'Edit Event: ' . $event->getName(),
            'error' => $error,
            'filteredContent' => $filteredContent,
            'errors' => $errors
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

    /**
     * Check if text contains bad words using PurgoMalum API
     * 
     * @param string $expectedDate Expected date string
     * @return bool True if the date is valid, false otherwise
     */
    private function isValidDate(string $expectedDate): bool
    {
        try {
            new \DateTime($expectedDate);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    #[Route('/{eventId}/milestones/new', name: 'app_milestone_new', methods: ['GET', 'POST'])]
    public function newMilestone(Request $request, int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        $error = null;
        $filteredContent = null;
        $errors = [];
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $milestone = new EventMilestone();
        $milestone->setEvent($event);
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $expectedDate = $request->request->get('expectedDate');
            $completionDate = $request->request->get('completionDate');
            $status = $request->request->get('status');
            
            // Validation
            if (empty($name)) {
                $errors['name'] = 'Milestone name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Milestone name must be at least 3 characters';
            } elseif (strlen($name) > 100) {
                $errors['name'] = 'Milestone name cannot exceed 100 characters';
            }
            
            if (empty($expectedDate)) {
                $errors['expectedDate'] = 'Expected date is required';
            } else {
                try {
                    new \DateTime($expectedDate);
                } catch (\Exception $e) {
                    $errors['expectedDate'] = 'Invalid date format';
                }
            }
            
            if (!empty($completionDate)) {
                try {
                    $completionDateTime = new \DateTime($completionDate);
                    $expectedDateTime = new \DateTime($expectedDate);
            
                    if ($completionDateTime < $expectedDateTime && $status === 'Completed') {
                        $errors['completionDate'] = 'Completion date cannot be earlier than expected date for completed status';
                    }
                } catch (\Exception $e) {
                    $errors['completionDate'] = 'Invalid date format';
                }
            }
            
            if (empty($status)) {
                $errors['status'] = 'Status is required';
            } elseif (!in_array($status, ['Not_Started', 'Started', 'Completed', 'Delay'])) {
                $errors['status'] = 'Invalid status value';
            }
            
            // Check for bad words only if there are no validation errors
            if (empty($errors)) {
                // Check for bad words in the name and status
                $nameCheck = $this->badWordFilter->containsBadWords($name);
                $statusCheck = $status ? $this->badWordFilter->containsBadWords($status) : ['containsBadWords' => false];
                
                if ($nameCheck['containsBadWords'] || $statusCheck['containsBadWords']) {
                    $error = 'Your submission contains inappropriate content and cannot be accepted.';
                    $filteredContent = [];
                    
                    // Add specific details about what was flagged
                    if ($nameCheck['containsBadWords']) {
                        $error .= ' Please revise the milestone name.';
                        $filteredContent['name'] = [
                            'original' => $name,
                            'filtered' => $nameCheck['filtered']
                        ];
                    }
                    if ($statusCheck['containsBadWords']) {
                        $error .= ' Please revise the status.';
                        $filteredContent['status'] = [
                            'original' => $status,
                            'filtered' => $statusCheck['filtered']
                        ];
                    }
                    
                    $this->addFlash('danger', $error);
                } else {
                    // No bad words detected and validation passed, proceed with saving
                    $milestone->setName($name);
                    $milestone->setExpectedDate(new \DateTime($expectedDate));
                    
                    if ($completionDate) {
                        $milestone->setCompletionDate(new \DateTime($completionDate));
                    }
                    
                    $milestone->setStatus($status);
            
            $this->milestoneRepository->save($milestone, true);
            
            $this->addFlash('success', 'Milestone created successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $eventId]);
                }
            }
        }
        
        return $this->render('milestone/new.html.twig', [
            'event' => $event,
            'page_title' => 'Add Milestone to: ' . $event->getName(),
            'error' => $error,
            'filteredContent' => $filteredContent,
            'errors' => $errors,
            'milestone' => $milestone
        ]);
    }

    #[Route('/milestones/edit/{id}', name: 'app_milestone_edit', methods: ['GET', 'POST'])]
    public function editMilestone(Request $request, int $id): Response
    {
        $milestone = $this->milestoneRepository->find($id);
        $error = null;
        $filteredContent = null;
        $errors = [];
        
        if (!$milestone) {
            throw $this->createNotFoundException('Milestone not found');
        }
        
        $event = $milestone->getEvent();
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $expectedDate = $request->request->get('expectedDate');
            $completionDate = $request->request->get('completionDate');
            $status = $request->request->get('status');
            
            // Validation
            if (empty($name)) {
                $errors['name'] = 'Milestone name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Milestone name must be at least 3 characters';
            } elseif (strlen($name) > 100) {
                $errors['name'] = 'Milestone name cannot exceed 100 characters';
            }
            
            if (empty($expectedDate)) {
                $errors['expectedDate'] = 'Expected date is required';
            } else {
                try {
                    new \DateTime($expectedDate);
                } catch (\Exception $e) {
                    $errors['expectedDate'] = 'Invalid date format';
                }
            }
            
            if (!empty($completionDate)) {
                try {
                    $completionDateTime = new \DateTime($completionDate);
                    $expectedDateTime = new \DateTime($expectedDate);
                    
                    if ($completionDateTime < $expectedDateTime && $status === 'Completed') {
                        $errors['completionDate'] = 'Completion date cannot be earlier than expected date for completed status';
            }
                } catch (\Exception $e) {
                    $errors['completionDate'] = 'Invalid date format';
                }
            }
            
            if (empty($status)) {
                $errors['status'] = 'Status is required';
            } elseif (!in_array($status, ['Not_Started', 'Started', 'Completed', 'Delay'])) {
                $errors['status'] = 'Invalid status value';
            }
            
            // Check for bad words only if there are no validation errors
            if (empty($errors)) {
                // Check for bad words in the name and status
                $nameCheck = $this->badWordFilter->containsBadWords($name);
                $statusCheck = $status ? $this->badWordFilter->containsBadWords($status) : ['containsBadWords' => false];
                
                if ($nameCheck['containsBadWords'] || $statusCheck['containsBadWords']) {
                    $error = 'Your submission contains inappropriate content and cannot be accepted.';
                    $filteredContent = [];
                    
                    // Add specific details about what was flagged
                    if ($nameCheck['containsBadWords']) {
                        $error .= ' Please revise the milestone name.';
                        $filteredContent['name'] = [
                            'original' => $name,
                            'filtered' => $nameCheck['filtered']
                        ];
                    }
                    if ($statusCheck['containsBadWords']) {
                        $error .= ' Please revise the status.';
                        $filteredContent['status'] = [
                            'original' => $status,
                            'filtered' => $statusCheck['filtered']
                        ];
                    }
                    
                    $this->addFlash('danger', $error);
                } else {
                    // No bad words detected and validation passed, proceed with saving
                    $milestone->setName($name);
                    $milestone->setExpectedDate(new \DateTime($expectedDate));
                    
                    if ($completionDate) {
                        $milestone->setCompletionDate(new \DateTime($completionDate));
                    } else {
                        $milestone->setCompletionDate(null);
                    }
                    
                    $milestone->setStatus($status);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Milestone updated successfully');
            return $this->redirectToRoute('app_event_view', ['id' => $event->getId()]);
                }
            }
        }
        
        return $this->render('milestone/edit.html.twig', [
            'milestone' => $milestone,
            'event' => $event,
            'page_title' => 'Edit Milestone: ' . $milestone->getName(),
            'error' => $error,
            'filteredContent' => $filteredContent,
            'errors' => $errors
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