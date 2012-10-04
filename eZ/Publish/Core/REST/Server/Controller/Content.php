<?php
/**
 * File containing the Content controller class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Controller;
use eZ\Publish\Core\REST\Common\UrlHandler;
use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Common\Input;
use eZ\Publish\Core\REST\Common\Exceptions;
use eZ\Publish\Core\REST\Server\Values;
use eZ\Publish\Core\REST\Server\Controller as RestController;

use eZ\Publish\API\Repository\Values\Content\Relation;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\Core\REST\Server\Exceptions\ForbiddenException;

use \eZ\Publish\API\Repository\ContentService;
use \eZ\Publish\API\Repository\LocationService;
use \eZ\Publish\API\Repository\SectionService;
use \eZ\Publish\API\Repository\SearchService;

use Qafoo\RMF;

/**
 * Content controller
 */
class Content extends RestController
{
    /**
     * Input dispatcher
     *
     * @var \eZ\Publish\Core\REST\Common\Input\Dispatcher
     */
    protected $inputDispatcher;

    /**
     * URL handler
     *
     * @var \eZ\Publish\Core\REST\Common\UrlHandler
     */
    protected $urlHandler;

    /**
     * Content service
     *
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * Location service
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Section service
     *
     * @var \eZ\Publish\API\Repository\SectionService
     */
    protected $sectionService;

    /**
     * Search service
     *
     * @var \eZ\Publish\API\Repository\SearchService
     */
    protected $searchService;

    /**
     * Construct controller
     *
     * @param \eZ\Publish\Core\REST\Common\Input\Dispatcher $inputDispatcher
     * @param \eZ\Publish\Core\REST\Common\UrlHandler $urlHandler
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\SectionService $sectionService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     */
    public function __construct( Input\Dispatcher $inputDispatcher, UrlHandler $urlHandler, ContentService $contentService, LocationService $locationService, SectionService $sectionService, SearchService $searchService )
    {
        $this->inputDispatcher = $inputDispatcher;
        $this->urlHandler      = $urlHandler;
        $this->contentService  = $contentService;
        $this->locationService = $locationService;
        $this->sectionService  = $sectionService;
        $this->searchService   = $searchService;
    }

    /**
     * Load a content info by remote ID
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ContentList
     */
    public function loadContentInfoByRemoteId( RMF\Request $request )
    {
        $contentInfo = $this->contentService->loadContentInfoByRemoteId(
            // GET variable
            $request->variables['remoteId']
        );

        return new Values\ContentList(
            array(
                new Values\RestContent(
                    $contentInfo,
                    $this->locationService->loadLocation( $contentInfo->mainLocationId )
                )
            )
        );
    }

    /**
     * Loads a content info, potentially with the current version embedded
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestContent
     */
    public function loadContent( RMF\Request $request )
    {
        $questionMark = strpos( $request->path, '?' );
        $requestPath = $questionMark !== false ? substr( $request->path, 0, $questionMark ) : $request->path;

        $urlValues = $this->urlHandler->parse( 'object', $requestPath );

        $contentInfo = $this->contentService->loadContentInfo( $urlValues['object'] );
        $mainLocation = $this->locationService->loadLocation( $contentInfo->mainLocationId );

        $contentVersion = null;
        if ( $this->getMediaType( $request ) === 'application/vnd.ez.api.content' )
        {
            $languages = null;
            if ( isset( $request->variables['languages'] ) )
            {
                $languages = explode( ',', $request->variables['languages'] );
            }

            $contentVersion = $this->contentService->loadContent( $urlValues['object'], $languages );
        }

        return new Values\RestContent( $contentInfo, $mainLocation, $contentVersion, $request->path );
    }

    /**
     * Updates a content's metadata
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestContent
     */
    public function updateContentMetadata( RMF\Request $request )
    {
        $updateStruct = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $request->contentType ),
                $request->body
            )
        );

        $values = $this->urlHandler->parse( 'object', $request->path );
        $contentId = (int)$values['object'];
        $contentInfo = $this->contentService->loadContentInfo( $contentId );

        // update section
        if ( $updateStruct->sectionId !== null )
        {
            // @todo Exception handling. Section not found ? Not authorized ?
            $section = $this->sectionService->loadSection( $updateStruct->sectionId );
            $this->sectionService->assignSection( $contentInfo, $section );
            $updateStruct->sectionId = null;
        }

        // update content
        // @todo An exception may be thrown if sectionId was the only property left in the struct. Maybe test the properties list manually here ?
        $this->contentService->updateContentMetadata( $contentInfo, $updateStruct );

        $contentInfo = $this->contentService->loadContentInfo( $contentId );
        try
        {
            $locationInfo = $this->locationService->loadLocation( $contentInfo->mainLocationId );
        }
        catch ( NotFoundException $e )
        {
            $locationInfo = null;
        }
        return new Values\RestContent(
            $contentInfo,
            $locationInfo
        );
    }

    /**
     * Loads a specific version of a given content object
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\TemporaryRedirect
     */
    public function redirectCurrentVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectCurrentVersion', $request->path );

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfo( $urlValues['object'] )
        );

        return new Values\TemporaryRedirect(
            $this->urlHandler->generate(
                'objectVersion',
                array(
                    'object' => $urlValues['object'],
                    'version' => $versionInfo->versionNo
                )
            ),
            'Version'
        );
    }

    /**
     * Loads a specific version of a given content object
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContentInVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersion', $request->path );

        return $this->contentService->loadContent(
            $urlValues['object'],
            null,               // TODO: Implement using language filter on request URI
            $urlValues['version']
        );
    }

    /**
     * Creates a new content draft assigned to the authenticated user.
     * If a different userId is given in the input it is assigned to the
     * given user but this required special rights for the authenticated
     * user (this is useful for content staging where the transfer process
     * does not have to authenticate with the user which created the content
     * object in the source server). The user has to publish the content if
     * it should be visible.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedContent
     */
    public function createContent( RMF\Request $request )
    {
        $contentCreate = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $request->contentType ),
                $request->body
            )
        );

        $content = $this->contentService->createContent(
            $contentCreate->contentCreateStruct,
            array( $contentCreate->locationCreateStruct )
        );

        return new Values\CreatedContent(
            array(
                'content' => new Values\RestContent(
                    $content->contentInfo,
                    null,
                    $this->getMediaType( $request ) === 'application/vnd.ez.api.content' ? $content : null
                )
            )
        );
    }

    /**
     * The content is deleted. If the content has locations (which is required in 4.x)
     * on delete all locations assigned the content object are deleted via delete subtree.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceDeleted
     */
    public function deleteContent( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'object', $request->path );

        $this->contentService->deleteContent(
            $this->contentService->loadContentInfo( $urlValues['object'] )
        );

        return new Values\ResourceDeleted();
    }

    /**
     * Creates a new content object as copy under the given parent location given in the destination header.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceCreated
     */
    public function copyContent( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'object', $request->path );
        $destinationValues = $this->urlHandler->parse( 'location', $request->destination );

        $parentLocationParts = explode( '/', $destinationValues['location'] );
        $copiedContent = $this->contentService->copyContent(
            $this->contentService->loadContentInfo( $urlValues['object'] ),
            $this->locationService->newLocationCreateStruct( array_pop( $parentLocationParts ) )
        );

        return new Values\ResourceCreated(
            $this->urlHandler->generate(
                'object',
                array( 'object' => $copiedContent->id )
            )
        );
    }

    /**
     * Returns a list of all versions of the content. This method does not
     * include fields and relations in the Version elements of the response.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\VersionList
     */
    public function loadContentVersions( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersions', $request->path );

        return new Values\VersionList(
            $this->contentService->loadVersions(
                $this->contentService->loadContentInfo( $urlValues['object'] )
            ),
            $request->path
        );
    }

    /**
     * The version is deleted
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceDeleted
     */
    public function deleteContentVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersion', $request->path );

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfo( $urlValues['object'] ),
            $urlValues['version']
        );

        if ( $versionInfo->status === VersionInfo::STATUS_PUBLISHED )
        {
            throw new ForbiddenException( 'Version in status PUBLISHED cannot be deleted' );
        }

        $this->contentService->deleteVersion(
            $versionInfo
        );

        return new Values\ResourceDeleted();
    }

    /**
     * The system creates a new draft version as a copy from the given version
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedVersion
     */
    public function createDraftFromVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersion', $request->path );

        $contentInfo = $this->contentService->loadContentInfo( $urlValues['object'] );
        $contentDraft = $this->contentService->createContentDraft(
            $contentInfo,
            $this->contentService->loadVersionInfo(
                $contentInfo, $urlValues['version']
            )
        );

        return new Values\CreatedVersion(
            array(
                'version' => $contentDraft
            )
        );
    }

    /**
     * The system creates a new draft version as a copy from the current version
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedVersion
     */
    public function createDraftFromCurrentVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectCurrentVersion', $request->path );

        $contentInfo = $this->contentService->loadContentInfo( $urlValues['object'] );
        $versionInfo = $this->contentService->loadVersionInfo(
            $contentInfo
        );

        if ( $versionInfo->status === VersionInfo::STATUS_DRAFT )
        {
            throw new ForbiddenException( 'Current version is already in status DRAFT' );
        }

        $contentDraft = $this->contentService->createContentDraft( $contentInfo );

        return new Values\CreatedVersion(
            array(
                'version' => $contentDraft
            )
        );
    }

    /**
     * A specific draft is updated.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function updateVersion( RMF\Request $request )
    {
        $questionMark = strpos( $request->path, '?' );
        $requestPath = $questionMark !== false ? substr( $request->path, 0, $questionMark ) : $request->path;

        $urlValues = $this->urlHandler->parse( 'objectVersion', $requestPath );

        $contentUpdateStruct = $this->inputDispatcher->parse(
            new Message(
                array(
                    'Content-Type' => $request->contentType,
                    // @todo Needs refactoring! Temporary solution so parser has access to URL
                    'Url' => $requestPath
                ),
                $request->body
            )
        );

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfo( $urlValues['object'] ),
            $urlValues['version']
        );

        if ( $versionInfo->status !== VersionInfo::STATUS_DRAFT )
        {
            throw new ForbiddenException( 'Only version in status DRAFT can be updated' );
        }

        $this->contentService->updateContent( $versionInfo, $contentUpdateStruct );

        $languages = null;
        if ( isset( $request->variables['languages'] ) )
        {
            $languages = explode( ',', $request->variables['languages'] );
        }

        // Reload the content to handle languages GET parameter
        return $this->contentService->loadContent( $urlValues['object'], $languages, $versionInfo->versionNo );
    }

    /**
     * The content version is published
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\NoContent
     */
    public function publishVersion( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersion', $request->path );

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfo( $urlValues['object'] ),
            $urlValues['version']
        );

        if ( $versionInfo->status !== VersionInfo::STATUS_DRAFT )
        {
            throw new ForbiddenException( 'Only version in status DRAFT can be published' );
        }

        $this->contentService->publishVersion(
            $versionInfo
        );

        return new Values\NoContent();
    }

    /**
     * Redirects to the relations of the current version
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\TemporaryRedirect
     */
    public function redirectCurrentVersionRelations( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectrelations', $request->path );

        $contentInfo = $this->contentService->loadContentInfo( $urlValues['object'] );
        return new Values\TemporaryRedirect(
            $this->urlHandler->generate(
                'objectVersionRelations',
                array(
                    'object' => $urlValues['object'],
                    'version' => $contentInfo->currentVersionNo
                )
            ),
            'RelationList'
        );
    }

    /**
     * Loads the relations of the given version
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RelationList
     */
    public function loadVersionRelations( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersionRelations', $request->path );

        $relationList = $this->contentService->loadRelations(
            $this->contentService->loadVersionInfo(
                $this->contentService->loadContentInfo( $urlValues['object'] ),
                $urlValues['version']
            )
        );

        return new Values\RelationList( $relationList, $urlValues['object'], $urlValues['version'] );
    }

    /**
     * Loads a relation for the given content object and version
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestRelation
     */
    public function loadVersionRelation( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersionRelation', $request->path );

        $relationList = $this->contentService->loadRelations(
            $this->contentService->loadVersionInfo(
                $this->contentService->loadContentInfo( $urlValues['object'] ),
                $urlValues['version']
            )
        );

        foreach ( $relationList as $relation )
        {
            if ( $relation->id == $urlValues['relation'] )
            {
                return new Values\RestRelation( $relation, $urlValues['object'], $urlValues['version'] );
            }
        }

        throw new Exceptions\NotFoundException( "Relation not found: '{$request->path}'." );
    }

    /**
     * Deletes a relation of the given draft.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceDeleted
     */
    public function removeRelation( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersionRelation', $request->path );

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfo( $urlValues['object'] ),
            $urlValues['version']
        );

        $versionRelations = $this->contentService->loadRelations( $versionInfo );
        foreach ( $versionRelations as $relation )
        {
            if ( $relation->id == $urlValues['relation'] )
            {
                if ( $relation->type !== Relation::COMMON )
                {
                    throw new ForbiddenException( "Relation is not of type COMMON" );
                }

                if ( $versionInfo->status !== VersionInfo::STATUS_DRAFT )
                {
                    throw new ForbiddenException( "Relation of type COMMON can only be removed from drafts" );
                }

                $this->contentService->deleteRelation( $versionInfo, $relation->getDestinationContentInfo() );
                return new Values\ResourceDeleted();
            }
        }

        throw new Exceptions\NotFoundException( "Relation not found: '{$request->path}'." );
    }

    /**
     * Creates a new relation of type COMMON for the given draft.
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedRelation
     */
    public function createRelation( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'objectVersionRelations', $request->path );
        $destinationContentId = $updateStruct = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $request->contentType ),
                $request->body
            )
        );

        $contentInfo = $this->contentService->loadContentInfo( $urlValues['object'] );
        $versionInfo = $this->contentService->loadVersionInfo( $contentInfo, $urlValues['version'] );
        if ( $versionInfo->status !== VersionInfo::STATUS_DRAFT )
        {
            throw new ForbiddenException( "Relation of type COMMON can only be added to drafts" );
        }

        try
        {
            $destinationContentInfo = $this->contentService->loadContentInfo( $destinationContentId );
        }
        catch ( NotFoundException $e )
        {
            throw new ForbiddenException( $e->getMessage() );
        }

        $existingRelations = $this->contentService->loadRelations( $versionInfo );
        foreach ( $existingRelations as $existingRelation )
        {
            if ( $existingRelation->getDestinationContentInfo()->id == $destinationContentId )
            {
                throw new ForbiddenException( "Relation of type COMMON to selected destination content ID already exists" );
            }
        }

        $relation = $this->contentService->addRelation( $versionInfo, $destinationContentInfo );
        return new Values\CreatedRelation(
            array(
                'relation' => new Values\RestRelation( $relation, $urlValues['object'], $urlValues['version'] )
            )
        );
    }

    /**
     * Creates and executes a content view
     *
     * @param \Qafoo\RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestExecutedView
     */
    public function createView( RMF\Request $request )
    {
        $viewInput = $this->inputDispatcher->parse(
            new Message(
                array( 'Content-Type' => $request->contentType ),
                $request->body
            )
        );
        return new Values\RestExecutedView(
            array(
                 'identifier'    => $viewInput->identifier,
                 'searchResults' => $this->searchService->findContent( $viewInput->query ),
            )
        );
    }

    /**
     * Extracts the requested media type from $request
     *
     * @param RMF\Request $request
     * @return string
     */
    protected function getMediaType( RMF\Request $request )
    {
        foreach ( $request->mimetype as $mimeType )
        {
            if ( preg_match( '(^([a-z0-9-/.]+)\+.*$)', $mimeType['value'], $matches ) )
            {
                return $matches[1];
            }
        }
        return 'unknown/unknown';
    }
}
