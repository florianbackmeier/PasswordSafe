<?php
namespace App\Service;

class CategoryService
{

    public function generateUrlSlug($category)
    {
        return str_replace('=', '', base64_encode($category));
    }

    public function getCategoryFromUrlSlug($slug)
    {
        return base64_decode($slug . '==');
    }

}
