<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\ProfileManagerInterface;

class YounesTestsController extends Controller
{
    /**
     * @Route("/younes/tests", name="younes_tests")
     * @param Request $request
     * @return Response;
     */
    public function index(Request $request, ProfileManagerInterface $profileManager): Response
    {
     
        $nickname = $request->request->get('nickname');
        $user_email = null;
        
        if($profileManager->findByNickname($nickname))
            $user_email = $profileManager->findByNickname($nickname)->getUser()->getEmailAuthRecipient();
       
        return $this->render('younes_tests/index.html.twig', [
            'controller_name' => 'YounesTestsController',
            'nickname' => $nickname,
            'user_email' => $user_email
        ]);
    }

    /**
     * @Route("/younes/tests/form", name="younes_tests_form")
     */
    public function form()
    {
        return $this->render('younes_tests/form.html.twig');
    }    
}
