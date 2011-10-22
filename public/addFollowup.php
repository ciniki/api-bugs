<?php
//
// Description
// -----------
// This function will add a followup to a bug
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The business the bug is attached to.
// bug_id:				The bug to attach the follow up.
// content:				The content of the reply to attach.
// 
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_bugs_addFollowup($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'bug_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No bug specified'),
		'content'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No content'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Get the module options
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/getOptions.php');
	$rc = ciniki_bugs_getOptions($ciniki, $args['business_id'], 'ciniki.bugs.addFollowup');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$options = $rc['options'];

	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/bugs/private/checkAccess.php');
	$rc = ciniki_bugs_checkAccess($ciniki, $args['business_id'], 'ciniki.bugs.addFollowup', $args['bug_id'], 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['user_id'] = $ciniki['session']['user']['id'];

	// 
	// Turn of auto commit in the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add a followup 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollowup.php');
	$rc = ciniki_core_threadAddFollowup($ciniki, 'bugs', 'bug_followups', 'bug', $args['bug_id'], $args);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	//
	// Make sure the user is attached as a follower.  They may already be attached, but it
	// will make sure the flag is set.
	// 
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollower.php');
	$rc = ciniki_core_threadAddFollower($ciniki, 'bugs', 'bug_users', 'bug', $args['bug_id'], $ciniki['session']['user']['id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'bugs');
		return $rc;
	}

	//
	// FIXME: Notify the other users on this thread there was an update.
	//
	// ciniki_core_threadNotifyUsers($ciniki, 'bugs', 'bug_users', 'followup', 
	//

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'bugs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
	
}
?>
