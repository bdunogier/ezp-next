<?php

/**
 * File containing the Section ValueObjectVisitor class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace eZ\Publish\Core\REST\Server\Output\ValueObjectVisitor;

use eZ\Publish\API\Repository\Values\Content as ApiValues;
use eZ\Publish\Core\REST\Common\Exceptions;
use eZ\Publish\Core\REST\Common\Output\ValueObjectVisitor;
use eZ\Publish\Core\REST\Common\Output\Generator;
use eZ\Publish\Core\REST\Common\Output\Visitor;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\REST\Server\Values\RestContent as RestContentValue;

/**
 * Visits a SearchResult into a Rest Output.
 */
class SearchResult extends ValueObjectVisitor
{
    /**
     * Location service.
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Content service.
     *
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * ContentType service.
     *
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        ContentTypeService $contentTypeService
    ) {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
    }

    public function visit(Visitor $visitor, Generator $generator, $searchResult)
    {
        $generator->startObjectElement('SearchResult');
        $visitor->setHeader('Content-Type', $generator->getMediaType('SearchResult'));

        // BEGIN Result metadata
        $generator->startValueElement('count', $searchResult->totalCount);
        $generator->endValueElement('count');

        $generator->startValueElement('time', $searchResult->time);
        $generator->endValueElement('time');

        $generator->startValueElement('timedOut', $generator->serializeBool($searchResult->timedOut));
        $generator->endValueElement('timedOut');

        $generator->startValueElement('maxScore', $searchResult->maxScore);
        $generator->endValueElement('maxScore');
        // END Result metadata

        // BEGIN searchHits
        $generator->startHashElement('searchHits');
        $generator->startList('searchHit');

        foreach ($searchResult->searchHits as $searchHit) {
            $generator->startObjectElement('searchHit');

            $generator->startAttribute('score', (float)$searchHit->score);
            $generator->endAttribute('score');

            $generator->startAttribute('index', (string)$searchHit->index);
            $generator->endAttribute('index');

            $generator->startObjectElement('value');

            // @todo Refactor
            if ($searchHit->valueObject instanceof ApiValues\Content) {
                /** @var \eZ\Publish\API\Repository\Values\Content\ContentInfo $contentInfo */
                $contentInfo = $searchHit->valueObject->contentInfo;
                $valueObject = new RestContentValue(
                    $contentInfo,
                    $this->locationService->loadLocation($contentInfo->mainLocationId),
                    $searchHit->valueObject,
                    $this->contentTypeService->loadContentType($contentInfo->contentTypeId),
                    $this->contentService->loadRelations($searchHit->valueObject->getVersionInfo())
                );
            } elseif ($searchHit->valueObject instanceof ApiValues\Location) {
                $valueObject = $searchHit->valueObject;
            } elseif ($searchHit->valueObject instanceof ApiValues\ContentInfo) {
                $valueObject = new RestContentValue($searchHit->valueObject);
            } else {
                throw new Exceptions\InvalidArgumentException('Unhandled object type');
            }

            $visitor->visitValueObject($valueObject);
            $generator->endObjectElement('value');
            $generator->endObjectElement('searchHit');
        }

        $generator->endList('searchHit');
        $generator->endHashElement('searchHits');

        $generator->endObjectElement('SearchResult');
    }
}
