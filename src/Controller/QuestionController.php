<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Question;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client')]
class QuestionController extends AbstractController
{
    #[Route('/chat', name: 'question_chat', methods: ['GET', 'POST'])]
    public function chat(
        Request $request,
        EntityManagerInterface $em,
        ReponseRepository $reponseRepository
    ): Response {
        // Find or create a default question
        $questionRepo = $em->getRepository(Question::class);
        $question = $questionRepo->findOneBy([], ['id' => 'DESC']) ?? new Question();
        
        // If this is a new question, set a default name and save it
        if (!$question->getId()) {
            $question->setName('General Chat');
            $em->persist($question);
            $em->flush();
        }
        
        $message = new Message();
        $form = $this->createFormBuilder($message)
            ->add('sender')
            ->add('content')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setCreatedAt(new \DateTimeImmutable());
            $message->setQuestion($question);

            // Affecte la première réponse trouvée (à adapter selon ton besoin réel)
            $reponse = $reponseRepository->findOneBy([]);
            if ($reponse) {
                $message->setReponse($reponse);
            }

            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('question_chat');
        }
        
        return $this->render('client/chat.html.twig', [
            'form' => $form->createView(),
            'messages' => $question->getMessages(),
            'question' => $question,
        ]);
    }
}