<?php

namespace App\Controller\traits;

trait ControllerTraits
{
    public function pagination(array $list, int $itemPerPage): array
    {
        $currentPage = $_GET['page'] ?? 1;
        $requiredPages = ceil(count($list) / $itemPerPage);

        $pagination = [];
        $pagination[] = 1;
        if ($currentPage > 3) {
            $pagination[] = '...';
        }
        if ($currentPage > 1 && $currentPage != 2) {
            $pagination[] = $currentPage - 1;
        }
        if ($currentPage != 1 && $currentPage != $requiredPages) {
            $pagination[] = $currentPage;
        }
        if ($requiredPages - $currentPage > 1) {
            $pagination[] = $currentPage + 1;
        }
        if ($requiredPages - $currentPage > 2) {
            $pagination[] = '...';
        }
        if ($requiredPages > 1) {
            $pagination[] = $requiredPages;
        }

        return $pagination;
    }
}