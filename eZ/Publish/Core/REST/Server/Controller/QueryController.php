<?php
/**
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\REST\Server\Controller;

use eZ\Publish\API\Repository\Exceptions\NotImplementedException;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;
use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use eZ\Publish\Core\REST\Server\Controller;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Server\Values;

/**
 * Controller that searches the repository using QueryTypes
 */
class QueryController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    private $searchService;

    /**
     * @var QueryTypeRegistry
     */
    private $queryTypeRegistry;

    public function __construct(SearchService $searchService, QueryTypeRegistry $queryTypeRegistry)
    {
        $this->searchService = $searchService;
        $this->queryTypeRegistry = $queryTypeRegistry;
    }
    public function queryAction($searchType, $queryTypeName, Request $request)
    {
        switch ($searchType )
        {
            case 'location':    $method = 'findLocations'; break;
            case 'content':     $method = 'findContent'; break;
            case 'contentInfo': $method = 'findContentInfo'; break;
            default:
                throw new NotFoundException("$searchType isn't a valid search type");
        }

        // How do you call something that, given a Request and a QueryType name,
        // returns the resulting Query ? QueryBuilder isn't specific enough,
        // as the input parameter IS expected to be a request.
        // $queryType->build($queryTypeName, $request)
        //
        // Or should it be a service that runs a Query instead ? Given what,
        // a QueryType ? Coupling would be tighter if it expected a QueryType, and
        // not a QueryTypeName
        //
        // $queryTypeRunner->run($queryType, $request);
        //
        // The QueryTypeRunner interface should NOT typehint on a Request,
        // as other implementations may expect a different argument.
        // How are callers supposed to know what is expected ?
        $queryType = $this->queryTypeRegistry->getQueryType($queryTypeName);
        $parameters = array_filter(
            $request->query->all(),
            function ($key) use ($queryType) {
                return in_array($key, $queryType->getSupportedParameters());
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this->searchService->$method($queryType->getQuery($parameters));
    }
}
