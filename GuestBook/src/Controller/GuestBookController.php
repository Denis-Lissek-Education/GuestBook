<?php

namespace App\Controller;

use App\Datamodel\GuestBookEntry;
use App\Entity\GuestBook;
use App\Entity\User;
use App\Form\GuestBookEntryType;
use App\Repository\GuestBookRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('guestbook', name: 'app_guest_book')]
class GuestBookController extends AbstractController
{
    private GuestBookRepository $guestBookRepo;
    private UserRepository $userRepo;

    public function __construct(private LoggerInterface $logger, private ManagerRegistry $doctrine){
        $this->guestBookRepo =  $doctrine->getRepository(GuestBook::class);
        $this->userRepo = $doctrine->getRepository(User::class);
    }

    #[Route('/list', name: '_list')]
    public function list(): Response
    {
        try {
        $guestBookEntries = $this->guestBookRepo->findAll();
        $users = $this->userRepo->findAll();
        } catch (\Throwable $e) {
            $this->logger->error('Thrown in ' . self::class . PHP_EOL . $e->getMessage() . PHP_EOL);
            return $this->render('error.html.twig', [
                'message' => 'There was an database related error, please try again later'
            ]);
        }
        
        return $this->render('guest_book/list.html.twig', [
            'guestBook' => $this->matchUserToGuestBookEntries($users, $guestBookEntries),
        ]);
    }

    #[Route('/add', name: '_add')]
    public function addEntry(Request $request): Response
    {
       $form = $this->createForm(GuestBookEntryType::class);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           $guestBookEntry = $form->getData();

            $user = new User();
            $user->setFirstName($guestBookEntry['firstName'])
                ->setLastName($guestBookEntry['lastName']);

            $this->doctrine->getManager()->persist($user);
            $this->doctrine->getManager()->flush();

            $guestBook = new GuestBook();
            $guestBook->setUserId($user->getId());

            $this->doctrine->getManager()->persist($guestBook);
            $this->doctrine->getManager()->flush();

           return $this->redirectToRoute('app_guest_book_list');
       }

       return $this->renderForm('guest_book/addEntry.html.twig', [
           'form' => $form,
       ]);
        
    }

    /**
     * @param User[] $users
     * @param GuestBook[] $guestBookEntries
     * @return GuestBookEntry[]
     */
    private function matchUserToGuestBookEntries(array $users, array $guestBookEntries): array
    {
        $matchedEntries = [];
        foreach($users as $user){
            foreach($guestBookEntries as $entry){ 
                if($entry->getUserId() === $user->getId()){
                    $matchedEntries[] = new GuestBookEntry($user, $entry);
                }
            }
        }
        
        return $matchedEntries;
    }
}
