<?php
/**
 * File containing the eZPublish RequestParser class
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Common\RequestParser;

/**
 * Pattern based Request parser pre-configured for eZ Publish
 */
class eZPublish extends Pattern
{
    /**
     * Map of URL types to their URL patterns
     *
     * @var array
     * @todo: Add sensible missing names
     */
    protected $map = array(
        'root'                      => '/api/ezp/v2/',
        'locations'                 => '/api/ezp/v2/content/locations',
        'locationByRemote'          => '/api/ezp/v2/content/locations?remoteId={location}',
        'locationById'              => '/api/ezp/v2/content/locations?id={location}',
        'locationChildren'          => '/api/ezp/v2/content/locations{&location}/children',
        'locationUrlAliases'        => '/api/ezp/v2/content/locations{&location}/urlaliases',
        'location'                  => '/api/ezp/v2/content/locations{&location}',
        'getImageVariation'         => '/api/ezp/v2/content/binary/images/{imageId}/variations/{variationIdentifier}',
        'objects'                   => '/api/ezp/v2/content/objects',
        'objectByRemote'            => '/api/ezp/v2/content/objects?remoteId={object}',
        'object'                    => '/api/ezp/v2/content/objects/{object}',
        'objectByLangCode'          => '/api/ezp/v2/content/objects/{object}/{lang_code}',
        'objectLocations'           => '/api/ezp/v2/content/objects/{object}/locations',
        'objectObjectStates'        => '/api/ezp/v2/content/objects/{object}/objectstates',
        'objectVersions'            => '/api/ezp/v2/content/objects/{object}/versions',
        'objectVersion'             => '/api/ezp/v2/content/objects/{object}/versions/{version}',
        'objectVersionRelations'    => '/api/ezp/v2/content/objects/{object}/versions/{version}/relations',
        'objectVersionRelation'     => '/api/ezp/v2/content/objects/{object}/versions/{version}/relations/{relation}',
        'objectCurrentVersion'      => '/api/ezp/v2/content/objects/{object}/currentversion',
        'objectrelations'           => '/api/ezp/v2/content/objects/{object}/relations',
        'objectrelation'            => '/api/ezp/v2/content/objects/{object}/relations/{relation}',
        'objectstategroups'         => '/api/ezp/v2/content/objectstategroups',
        'objectstategroup'          => '/api/ezp/v2/content/objectstategroups/{objectstategroup}',
        'objectstates'              => '/api/ezp/v2/content/objectstategroups/{objectstategroup}/objectstates',
        'objectstate'               => '/api/ezp/v2/content/objectstategroups/{objectstategroup}/objectstates/{objectstate}',
        'sections'                  => '/api/ezp/v2/content/sections',
        'section'                   => '/api/ezp/v2/content/sections/{section}',
        'sectionByIdentifier'       => '/api/ezp/v2/content/sections?identifier={section}',
        'trashItems'                => '/api/ezp/v2/content/trash',
        'trash'                     => '/api/ezp/v2/content/trash/{trash}',
        'typegroups'                => '/api/ezp/v2/content/typegroups',
        'typegroupByIdentifier'     => '/api/ezp/v2/content/typegroups?identifier={&typegroup}',
        'typegroup'                 => '/api/ezp/v2/content/typegroups/{typegroup}',
        'grouptypes'                => '/api/ezp/v2/content/typegroups/{typegroup}/types',
        'types'                     => '/api/ezp/v2/content/types',
        'typeByIdentifier'          => '/api/ezp/v2/content/types?identifier={type}',
        'typeByRemoteId'            => '/api/ezp/v2/content/types?remoteId={type}',
        'type'                      => '/api/ezp/v2/content/types/{type}',
        'typeFieldDefinitions'      => '/api/ezp/v2/content/types/{type}/fieldDefinitions',
        'typeFieldDefinition'       => '/api/ezp/v2/content/types/{type}/fieldDefinitions/{fieldDefinition}',
        'typeDraft'                 => '/api/ezp/v2/content/types/{type}/draft',
        'typeFieldDefinitionsDraft' => '/api/ezp/v2/content/types/{type}/draft/fieldDefinitions',
        'typeFieldDefinitionDraft'  => '/api/ezp/v2/content/types/{type}/draft/fieldDefinitions/{fieldDefinition}',
        'groupsOfType'              => '/api/ezp/v2/content/types/{type}/groups',
        'typeGroupAssign'           => '/api/ezp/v2/content/types/{type}/groups?group={&group}',
        'groupOfType'               => '/api/ezp/v2/content/types/{type}/groups/{group}',
        'urlWildcards'              => '/api/ezp/v2/content/urlwildcards',
        'urlWildcard'               => '/api/ezp/v2/content/urlwildcards/{urlwildcard}',
        'urlAliases'                => '/api/ezp/v2/content/urlaliases',
        'urlAlias'                  => '/api/ezp/v2/content/urlaliases/{urlalias}',
        'views'                     => '/api/ezp/v2/content/views',
        'view'                      => '/api/ezp/v2/content/views/{view}',
        'viewResults'               => '/api/ezp/v2/content/views/{view}/results',
        'groups'                    => '/api/ezp/v2/user/groups',
        'group'                     => '/api/ezp/v2/user/groups{&group}',
        'groupRoleAssignments'      => '/api/ezp/v2/user/groups{&group}/roles',
        'groupRoleAssignment'       => '/api/ezp/v2/user/groups{&group}/roles/{role}',
        'groupSubgroups'            => '/api/ezp/v2/user/groups{&group}/subgroups',
        'groupUsers'                => '/api/ezp/v2/user/groups{&group}/users',
        'rootUserGroup'             => '/api/ezp/v2/user/groups/root',
        'rootUserGroupSubGroups'    => '/api/ezp/v2/user/groups/subgroups',
        'roles'                     => '/api/ezp/v2/user/roles',
        'role'                      => '/api/ezp/v2/user/roles/{role}',
        'roleByIdentifier'          => '/api/ezp/v2/user/roles?identifier={role}',
        'policies'                  => '/api/ezp/v2/user/roles/{role}/policies',
        'policy'                    => '/api/ezp/v2/user/roles/{role}/policies/{policy}',
        'users'                     => '/api/ezp/v2/user/users',
        'user'                      => '/api/ezp/v2/user/users/{user}',
        'userDrafts'                => '/api/ezp/v2/user/users/{user}/drafts',
        'userGroups'                => '/api/ezp/v2/user/users/{user}/groups',
        'userGroupAssign'           => '/api/ezp/v2/user/users/{user}/groups?group={&group}',
        'userGroup'                 => '/api/ezp/v2/user/users/{user}/groups{&group}',
        'userRoleAssignments'       => '/api/ezp/v2/user/users/{user}/roles',
        'userRoleAssignment'        => '/api/ezp/v2/user/users/{user}/roles/{role}',
        'userPolicies'              => '/api/ezp/v2/user/policies?userId={user}',
        'userSession'               => '/api/ezp/v2/user/sessions/{sessionId}',
    );
}
