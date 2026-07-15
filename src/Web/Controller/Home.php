<?php

namespace Web\Controller;

use Core\Routing\Attribute\Route;

#[Route('/', name: 'web.home')]
class Home extends Controller
{

    public function build(): void
    {
        $this->assign('webName', '{{project name}}');
        $this->template('home.twig');
    }

}
