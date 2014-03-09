<?php
/**
 * File containing the RepositoryFactory class.
 *
 * @copyright Copyright (C) 2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace eZ\Publish\Core\REST\Client;

use eZ\Publish\Core\REST\Client\Repository;

use eZ\Publish\Core\REST\Common;

class RepositoryFactory
{
    /**
     * @param $baseURL
     * @param $username
     * @param $password
     * @return \eZ\Publish\API\Repository\Repository
     */
    public function create( $baseURL, $username, $password )
    {
        // Set communication encoding depending on environment defined in the
// phpunit.xml files. This defines what encoding will be generated and thus send
// to the server.
        $generator = getenv( 'backendEncoding' ) === 'json' ?
            new Common\Output\Generator\Json(
                new Common\Output\Generator\Json\FieldTypeHashGenerator()
            ) :
            new Common\Output\Generator\Xml(
                new Common\Output\Generator\Xml\FieldTypeHashGenerator()
            );

// The URL Handler is responsible for URL parsing and generation. It will be
// used in the output generators and in some parsing handlers.
        $requestParser = new Common\RequestParser\eZPublish();

// FieldTypes to be used in integration tests. The field types are only used
// in terms of conversions from and to hash values.
        $fieldTypes = array(
//    new Client\FieldType( new FieldType\Author\Type() ),
//    new Client\FieldType( new FieldType\Checkbox\Type() ),
//    new Client\FieldType( new FieldType\DateAndTime\Type() ),
//    new Client\FieldType( new FieldType\Float\Type() ),
//    new Client\FieldType( new FieldType\Integer\Type() ),
//    new Client\FieldType( new FieldType\Keyword\Type() ),
//    new Client\FieldType( new FieldType\MapLocation\Type() ),
//    new Client\FieldType( new FieldType\Relation\Type() ),
//    new Client\FieldType( new FieldType\RelationList\Type() ),
//    new Client\FieldType( new FieldType\Selection\Type() ),
//    new Client\FieldType( new FieldType\TextBlock\Type() ),
//    new Client\FieldType( new FieldType\TextLine\Type() ),
//    new Client\FieldType( new FieldType\Url\Type() ),
//    new Client\FieldType( new FieldType\User\Type() ),
//    new Client\FieldType( new FieldType\Null\Type( 'ezxmltext' ) ),         // @todo FIXME: Add correct type
//    new Client\FieldType( new FieldType\Null\Type( 'ezpage' ) ),            // @todo FIXME: Add correct type
        );

// The IntegrationTestRepository is only meant for integration tests. It
// handles sessions which run throughout a single test case run and submission
// of user information to the server, which needs a corresponding
// authenticator.
        $repository = new Repository(
        // The HTTP Client. Needs to implement the Client\HttpClient interface.
            new \eZ\Publish\Core\REST\Client\HttpClient\Guzzle(
                new \Guzzle\Http\Client( $baseURL, array('redirect.disable' => true) ),
                $username,
                $password
            ),
            new Common\Input\Dispatcher(
            // The parsing dispatcher is configured after the repository has been
            // created due to circular references
                $parsingDispatcher = new Common\Input\ParsingDispatcher(),
                array(
                    // Defines the available data format encoding handlers. used to
                    // process the input data and convert it into an array structure
                    // usable by the parsers.
                    //
                    // More generators should not be necessary to configure, unless new transport
                    // encoding formats need to be supported.
                    'json' => new Common\Input\Handler\Json(),
                    'xml'  => new Common\Input\Handler\Xml(),
                )
            ),
            new Common\Output\Visitor(
            // The generator defines what transport encoding format will be used.
            // This should either be the XML or JSON generator. In this case we use
            // a generator depending on an environment variable, as defined above.
                $generator,
                // The defined output visitors for the available value objects.
                //
                // If there is new data available, which should be visited and send to
                // the server extend this array. It always maps the class name of the
                // value object (or its parent class(es)) to the respective visitor
                // implementation instance.
                new Common\Output\ValueObjectVisitorDispatcher(
                    array(
                        '\\eZ\\Publish\\API\\Repository\\Values\\Content\\SectionCreateStruct'                   => new Output\ValueObjectVisitor\SectionCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\Content\\SectionUpdateStruct'                   => new Output\ValueObjectVisitor\SectionUpdateStruct( $requestParser ),
                        '\\eZ\\Publish\\Core\\REST\\Common\\Values\\SectionIncludingContentMetadataUpdateStruct' => new Output\ValueObjectVisitor\SectionIncludingContentMetadataUpdateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\User\\RoleCreateStruct'                         => new Output\ValueObjectVisitor\RoleCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\User\\RoleUpdateStruct'                         => new Output\ValueObjectVisitor\RoleUpdateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\User\\PolicyCreateStruct'                       => new Output\ValueObjectVisitor\PolicyCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\User\\Limitation'                               => new Output\ValueObjectVisitor\Limitation( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\User\\PolicyUpdateStruct'                       => new Output\ValueObjectVisitor\PolicyUpdateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\Content\\LocationCreateStruct'                  => new Output\ValueObjectVisitor\LocationCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\ObjectState\\ObjectStateGroupCreateStruct'      => new Output\ValueObjectVisitor\ObjectStateGroupCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\ObjectState\\ObjectStateGroupUpdateStruct'      => new Output\ValueObjectVisitor\ObjectStateGroupUpdateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\ObjectState\\ObjectStateCreateStruct'           => new Output\ValueObjectVisitor\ObjectStateCreateStruct( $requestParser ),
                        '\\eZ\\Publish\\API\\Repository\\Values\\ObjectState\\ObjectStateUpdateStruct'           => new Output\ValueObjectVisitor\ObjectStateUpdateStruct( $requestParser ),
                    )
                )
            ),
            $requestParser,
            $fieldTypes
        );

// Object with convenience methods for parsers
        $parserTools = new Common\Input\ParserTools();

// Parser for field values (using FieldTypes for toHash()/fromHash() operations)
        $fieldTypeParser = new Common\Input\FieldTypeParser(
            $repository->getContentService(),
            $repository->getContentTypeService(),
            $repository->getFieldTypeService(),
            new Common\FieldTypeProcessorRegistry()
        );

// The parsing dispatcher configures which parsers are used for which
// mime type. The mime types (content types) are provided *WITHOUT* an
// encoding type (+json / +xml).
//
// For each mime type you specify an instance of the parser which
// should be used to process the given mime type.
        $inputParsers = array(
            'application/vnd.ez.api.Version'              => new Input\Parser\Content(
                    $parserTools,
                    $repository->getContentService(),
                    // Circular reference, since REST does not transmit content info when
                    // loading the VersionInfo (which is included in the content)
                    new Input\Parser\VersionInfo( $parserTools, $repository->getContentService() ),
                    $fieldTypeParser
                ),
            'application/vnd.ez.api.ContentList'          => new Input\Parser\ContentList(),
            'application/vnd.ez.api.ContentInfo'          => new Input\Parser\ContentInfo(
                    $parserTools,
                    $repository->getContentTypeService()
                ),
            'application/vnd.ez.api.ContentType'          => new Input\Parser\ContentType(
                    $parserTools,
                    $repository->getContentTypeService()
                ),
            'application/vnd.ez.api.FieldDefinitionList'  => new Input\Parser\FieldDefinitionList(
                    $parserTools,
                    $repository->getContentTypeService()
                ),
            'application/vnd.ez.api.FieldDefinition'      => new Input\Parser\FieldDefinition(
                    $parserTools,
                    $fieldTypeParser
                ),
            'application/vnd.ez.api.SectionList'          => new Input\Parser\SectionList(),
            'application/vnd.ez.api.Section'              => new Input\Parser\Section(),
            'application/vnd.ez.api.ErrorMessage'         => new Input\Parser\ErrorMessage(),
            'application/vnd.ez.api.RoleList'             => new Input\Parser\RoleList(),
            'application/vnd.ez.api.Role'                 => new Input\Parser\Role(),
            'application/vnd.ez.api.Policy'               => new Input\Parser\Policy(),
            'application/vnd.ez.api.limitation'           => new Input\Parser\Limitation(),
            'application/vnd.ez.api.PolicyList'           => new Input\Parser\PolicyList(),
            'application/vnd.ez.api.RelationList'         => new Input\Parser\RelationList(),
            'application/vnd.ez.api.Relation'             => new Input\Parser\Relation(
                    $repository->getContentService()
                ),
            'application/vnd.ez.api.RoleAssignmentList'   => new Input\Parser\RoleAssignmentList(),
            'application/vnd.ez.api.RoleAssignment'       => new Input\Parser\RoleAssignment(),
            'application/vnd.ez.api.Location'             => new Input\Parser\Location(
                    $parserTools
                ),
            'application/vnd.ez.api.LocationList'         => new Input\Parser\LocationList(),
            'application/vnd.ez.api.ObjectStateGroup'     => new Input\Parser\ObjectStateGroup(
                    $parserTools
                ),
            'application/vnd.ez.api.ObjectStateGroupList' => new Input\Parser\ObjectStateGroupList(),
            'application/vnd.ez.api.ObjectState'          => new Input\Parser\ObjectState(
                    $parserTools
                ),
            'application/vnd.ez.api.ObjectStateList'      => new Input\Parser\ObjectStateList(),
        );
        foreach ( $inputParsers as $mimeType => $parser )
        {
            $parsingDispatcher->addParser( $mimeType, $parser );
        }

        return $repository;
    }
}
