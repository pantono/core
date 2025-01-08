<?php

namespace Pantono\Core\Router\Endpoint;

use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Core\Router\Model\EndpointDefinition;
use Symfony\Component\HttpFoundation\Response;
use Pantono\Authentication\Model\User;
use League\Fractal\TransformerAbstract;
use Pagerfanta\Pagerfanta;
use League\Fractal\Resource\Collection;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use Pantono\Core\Application\Helper\PaginationArrayAdapter;
use Pantono\Hydrator\Traits\LocatorAwareTrait;
use Pantono\Contracts\Filter\PageableInterface;
use Pantono\Authentication\Exception\AccessDeniedException;

abstract class AbstractEndpoint
{
    use LocatorAwareTrait;

    private Request $request;
    private ParameterBag $securityContext;
    private ?Session $session = null;
    private EndpointDefinition $endpoint;

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getSecurityContext(): ParameterBag
    {
        return $this->securityContext;
    }

    public function setSecurityContext(ParameterBag $securityContext): void
    {
        $this->securityContext = $securityContext;
    }

    public function getSession(): Session
    {
        if ($this->session === null) {
            throw new \RuntimeException('Session not set on request');
        }
        return $this->session;
    }

    public function setSession(?Session $session): void
    {
        $this->session = $session;
    }

    public function getEndpoint(): EndpointDefinition
    {
        return $this->endpoint;
    }

    public function setEndpoint(EndpointDefinition $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getCurrentUser(): ?User
    {
        return $this->securityContext->get('user');
    }

    public function getCurrentUserOrThrow(): User
    {
        if (!$this->securityContext->get('user')) {
            throw new AccessDeniedException('You are not logged in');
        }
        if ($this->securityContext->get('user') instanceof User === false) {
            throw new AccessDeniedException('Invalid user record found');
        }
        return $this->securityContext->get('user');
    }

    protected function paginateResults(TransformerAbstract $transformer, array $results, int $total, int $perPage, int $pageNumber = 1): Collection
    {
        /**
         * @var int<0,max> $total
         */
        $arrayAdapter = new PaginationArrayAdapter($results, $total);
        $pager = new Pagerfanta($arrayAdapter);
        $pager->setMaxPerPage($perPage);
        $pager->setCurrentPage($pageNumber);

        $collection = new Collection($pager->getCurrentPageResults(), $transformer);
        $collection->setPaginator(
            new PagerfantaPaginatorAdapter($pager, function ($page) {
                return $page;
            })
        );

        return $collection;
    }

    protected function paginateFilterResults(TransformerAbstract $transformer, PageableInterface $filter, array $results): Collection
    {
        return $this->paginateResults($transformer, $results, $filter->getTotalResults(), $filter->getPerPage(), $filter->getPage());
    }

    public function lookupRecord(string $className, mixed $id): mixed
    {
        return $this->getLocator()->lookupRecord($className, $id);
    }

    abstract public function processRequest(ParameterBag $parameters): array|ResourceAbstract|Response;
}
