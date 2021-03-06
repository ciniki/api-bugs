<?php
//
// Description
// -----------
// Search bugs by subject and date
//
// Returns
// -------
//
function ciniki_bugs_bugSearchFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'bugs', 'private', 'checkAccess');
    $rc = ciniki_bugs_checkAccess($ciniki, $args['tnid'], 'ciniki.bugs.bugSearchFull', 0, 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the number of messages in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT ciniki_bugs.id, type, priority, ciniki_bugs.status, subject, source, source_link, "
        . "IF((u1.perms&0x02)=2, 'yes', 'no') AS assigned, "
        . "IFNULL(u3.display_name, '') AS assigned_users "
        . "FROM ciniki_bugs "
        . "LEFT JOIN ciniki_bug_users AS u1 ON (ciniki_bugs.id = u1.bug_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
        . "LEFT JOIN ciniki_bug_users AS u2 ON (ciniki_bugs.id = u2.bug_id && (u2.perms&0x02) = 2) "
        . "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
        . "LEFT JOIN ciniki_bug_followups ON (ciniki_bugs.id = ciniki_bug_followups.bug_id) "
        . "WHERE ciniki_bugs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_bugs.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' "       // Open bugs/features
        . "AND (subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
    if( is_numeric($args['start_needle']) ) {
        $strsql .= "OR ciniki_bugs.id LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%'";
    }
    $strsql .= "OR ciniki_bug_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_bug_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
    if( is_integer($args['start_needle']) ) {
        $strsql .= "OR ciniki_bugs.id LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
    }

    $strsql .= ") "
        . "";
    // Check for public/private bugs, and if private make sure user created or is assigned
//  $strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to tenant
//          // created by the user requesting the list
//          . "OR ((perm_flags&0x01) = 1 AND ciniki_bugs.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//          // Assigned to the user requesting the list, and the user hasn't deleted the message
//          . "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x02) = 0x02 AND (u1.perms&0x10) <> 0x10 ) "
//          . ") "
    $strsql .= "GROUP BY ciniki_bugs.id, u3.id "
        . "ORDER BY assigned DESC, ciniki_bugs.id, u3.display_name "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.bugs', array(
        array('container'=>'bugs', 'fname'=>'id', 'name'=>'bug',
            'fields'=>array('id', 'type', 'subject', 'priority', 'status', 'assigned', 'assigned_users', 'source', 'source_link'), 
            'lists'=>array('assigned_users'),
            'maps'=>array('type'=>array('1'=>'Bug', '2'=>'Feature', '3'=>'Question'))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['bugs']) ) {
        return array('stat'=>'ok', 'bugs'=>array());
    }
    return array('stat'=>'ok', 'bugs'=>$rc['bugs']);
}
?>
