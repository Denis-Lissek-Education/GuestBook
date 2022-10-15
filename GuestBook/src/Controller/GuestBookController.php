<?php

namespace App\Controller;

use App\Entity\GuestBook;
use App\Entity\User;
use App\Repository\GuestBookRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('guestbook', name: 'app_guest_book')]
class GuestBookController extends AbstractController
{
    private LoggerInterface $logger;
    private GuestBookRepository $guestBookRepo;
    private UserRepository $userRepo;

    public function __construct(LoggerInterface $logger, private ManagerRegistry $doctrine){
        $this->logger = $logger;
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

        // Must do it this way, SQLite does not have joins
        $guestBook = [];
        foreach($users as $user){
            foreach($guestBookEntries as $entry){
                if($entry->getUserId() === $user->getId()){
                    $guestBook[] = [
                        'firstName' => $user->getFirstName(),
                        'lastName' => $user->getLastName(),
                        'checkedIn' => $entry->getCreatedAt()->format('d. m. Y, H:i:s'),
                    ];
                }
            }
        }

        return $this->render('guest_book/list.html.twig', [
            'guestBook' => $guestBook,
        ]);
    }
}
