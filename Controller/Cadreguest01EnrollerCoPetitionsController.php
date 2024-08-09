<?php

// This COmanage Registry enrollment plugin is intended to be used
// with an anonymous enrollment flow for the CADRE Guest CO. At the end of
// the flow the user is required to set a password for their
// new CADRE Guest ID.
//
// The following enrollment steps are implemented:
//
// finalize:
//   - Used to redirect back into the original CILogon
//     authentication flow.
//
// petitionerAttributes:
//   - Checks the email address input by the user into
//     the form and if email address is already known
//     for a registered user then stops the flow and
//     redirects.
//
//     Also prevents the user from using an email with
//     domain in the list of redlisted domains.
//
// provision:
//   - Requires the user to set a 
//     password for their new CADRE Guest ID.
//
// start:
//   - Records the return query parameter if present,
//     later used in the finalize step.

App::uses('CoPetitionsController', 'Controller');
App::uses('HtmlHelper', 'View/Helper');
App::uses('Cadre', 'CadreAuthenticator.Model');
App::uses('CadreAuthenticator', 'CadreAuthenticator.Model');
 
class Cadreguest01EnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "Cadreguest01EnrollerCoPetitions";
  public $uses = array("CoPetition");

  /**
   * Plugin functionality following finalize step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
//  protected function execute_plugin_finalize($id, $onFinish) {
//    $args = array();
//    $args['conditions']['CoPetition.id'] = $id;
//    $args['contain']['CoEnrollmentFlow'] = 'CoEnrollmentFlowFinMessageTemplate';
//    $args['contain']['EnrolleeCoPerson'] = array('PrimaryName', 'Identifier');
//    $args['contain']['EnrolleeCoPerson']['CoGroupMember'] = 'CoGroup';
//    $args['contain']['EnrolleeCoPerson']['CoPersonRole'][] = 'Cou';
//    $args['contain']['EnrolleeCoPerson']['CoPersonRole']['SponsorCoPerson'][] = 'PrimaryName';
//    $args['contain']['EnrolleeOrgIdentity'] = array('EmailAddress', 'PrimaryName');
//
//    $petition = $this->CoPetition->find('first', $args);
//    $this->log("Access02Enroller Finalize: Petition is " . print_r($petition, true));
//
//    $coId = $petition['CoPetition']['co_id'];
//    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];
//
//    // Find the ACCESS ID.
//    $accessId = null;
//    foreach($petition['EnrolleeCoPerson']['Identifier'] as $i) {
//      if($i['type'] == 'accessid') {
//        $accessId = $i['identifier'];
//      }
//    }
//
//    if(!empty($accessId)) {
//      // Attach an Identifier of type EPPN to the existing OrgIdentity and
//      // mark it as a login identifier.
//      $orgIdentityId = $petition['CoPetition']['enrollee_org_identity_id'];
//
//      $this->CoPetition->EnrolleeOrgIdentity->Identifier->clear();
//
//      $data = array();
//      $data['Identifier']['identifier'] = $accessId . '@access-ci.org';
//      $data['Identifier']['type'] = IdentifierEnum::ePPN;
//      $data['Identifier']['status'] = SuspendableStatusEnum::Active;
//      $data['Identifier']['login'] = true;
//      $data['Identifier']['org_identity_id'] = $orgIdentityId;
//
//      $this->CoPetition->EnrolleeOrgIdentity->Identifier->save($data);
//    }
//
//    // This step is completed so redirect to continue the flow.
//    $this->redirect($onFinish);
//  }

  /**
   * Plugin functionality following petitionerAttributes step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
   
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['EnrolleeCoPerson']['CoOrgIdentityLink']['OrgIdentity'] = 'EmailAddress';

    $petition = $this->CoPetition->find('first', $args);
    $this->log("Petitioner Attributes: Petition is " . print_r($petition, true));

    $coId = $petition['CoPetition']['co_id'];
    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];
    $coPersonRoleId = $petition['CoPetition']['enrollee_co_person_role_id'];
    $orgIdentityId = $petition['CoPetition']['enrollee_org_identity_id'];

    // Before continuing check the email address the anonymous user entered into
    // the form to see if this person was already registered and if so
    // return back into the flow.
    $args = array();
    $args['conditions']['EmailAddress.mail'] = $petition['EnrolleeCoPerson']
                                                        ['CoOrgIdentityLink']
                                                        [0]
                                                        ['OrgIdentity']
                                                        ['EmailAddress']
                                                        [0]
                                                        ['mail'];
    $args['contain'] = 'CoPerson';

    $emailAddress = $this->CoPetition->EnrolleeCoPerson->EmailAddress->find('all', $args);

    if($emailAddress) {
      // Loop over the EmailAddress and exclude those attached
      // to the current petition and those associated with CO Person
      // that does not have Active status.
      foreach($emailAddress as $e) {
        if($e['EmailAddress']['co_person_id'] != $coPersonId && $e['EmailAddress']['org_identity_id'] != $orgIdentityId) {
          if($e['CoPerson']['status'] == StatusEnum::Active) {
            // This is a duplicate.
            $msg = "Redirecting enrollment with email address " . $e['EmailAddress']['mail'];
            $msg = $msg . " and CO Person ID " . $e['EmailAddress']['co_person_id'];
            $msg = $msg . " stopping work on new CO Person ID $coPersonId";
            $this->log($msg);

            // Set the status on the new CO Person record to Duplicate.
            $opt = array();
            $opt['provision'] = false;

            $this->CoPetition->EnrolleeCoPersonRole->id = $coPersonRoleId;
            $this->CoPetition->EnrolleeCoPersonRole->saveField('status', StatusEnum::Duplicate, $opt);
            $this->CoPetition->EnrolleeCoPerson->id = $coPersonId;
            $this->CoPetition->EnrolleeCoPerson->saveField('status', StatusEnum::Duplicate, $opt);

            if($this->Session->check('cadreguest01.plugin.start.returnUrl')) {
              $url = $this->Session->consume('cadreguest01.plugin.start.returnUrl');

              // Add the idphint...

              // TODO stopped here...

              $this->redirect("https://identity.access-ci.org/email-exists");
            } else {
              // We don't have the URL back into the flow so just redirect to
              // the project's homepage.
              $this->redirect("https://cadre5safes.org.au/");
            }
          }
        }
      }
    }

    // TODO





    $this->redirect($onFinish);
  }

//  /**
//   * Plugin functionality following provision step
//   *
//   * @param Integer $id CO Petition ID
//   * @param Array $onFinish URL, in Cake format
//   */
//   
//  protected function execute_plugin_provision($id, $onFinish) {
//    $args = array();
//    $args['conditions']['CoPetition.id'] = $id;
//    $args['contain']['EnrolleeCoPerson'][] = 'Identifier';
//
//    $petition = $this->CoPetition->find('first', $args);
//    $this->log("Provision: Petition is " . print_r($petition, true));
//
//    $coId = $petition['CoPetition']['co_id'];
//    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];
//
//    // Find the ACCESS ID.
//    $accessId = null;
//    foreach($petition['EnrolleeCoPerson']['Identifier'] as $i) {
//      if($i['type'] == 'accessid') {
//        $accessId = $i['identifier'];
//      }
//    }
//
//    // We assume that the CO has one and only one instantiated KrbAuthenticator
//    // plugin and it is used for ACCESS ID password management.
//    $args = array();
//    $args['conditions']['Authenticator.co_id'] = $coId;
//    $args['conditions']['Authenticator.plugin'] = 'KrbAuthenticator';
//    $args['contain'] = false;
//
//    $authenticator = $this->CoPetition->Co->Authenticator->find('first', $args);
//
//    $args = array();
//    $args['conditions']['KrbAuthenticator.authenticator_id'] = $authenticator['Authenticator']['id'];
//    $args['contain'] = false;
//
//    $krbAuthenticatorModel = new KrbAuthenticator();
//
//    $krbAuthenticator = $krbAuthenticatorModel->find('first', $args);
//
//    $cfg = array();
//    $cfg['Authenticator'] = $authenticator['Authenticator'];
//    $cfg['KrbAuthenticator'] = $krbAuthenticator['KrbAuthenticator'];
//    $krbAuthenticatorModel->setConfig($cfg);
//
//    // Set the CoPetition ID to use as a hidden form element.
//    $this->set('co_petition_id', $id);
//
//    $this->set('vv_authenticator', $krbAuthenticator);
//    $this->set('vv_co_person_id', $coPersonId);
//    $this->set('vv_access_id', $accessId);
//
//    // Save the onFinish URL to which we must redirect after receiving
//    // the incoming POST data.
//    if(!$this->Session->check('access02.plugin.provision.onFinish')) {
//      $this->Session->write('access02.plugin.provision.onFinish', $onFinish);
//    }
//
//    // Process incoming POST data.
//    if($this->request->is('post')) {
//      try {
//        $krbAuthenticatorModel->manage($this->data, $coPersonId, true);
//        $onFinish = $this->Session->consume('access02.plugin.provision.onFinish');
//        $this->redirect($onFinish);
//      } catch (Exception $e) {
//        // Fall through to display the form again.
//        $this->set('vv_efwid', $this->data['Krb']['co_enrollment_flow_wedge_id']);
//        $this->Flash->set($e->getMessage(), array('key' => 'error'));
//      }
//    } // POST
//    
//    // GET fall through to view.
//  }

  /**
   * Plugin functionality following start step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
  protected function execute_plugin_start($id, $onFinish) {
    parse_str($_SERVER['QUERY_STRING'], $queryString);

    if(empty($queryString['return'])) {
      $this->redirect($onFinish);
    }

    $this->log("Cadreguest01Enroller Start: return query parameter is " . print_r($queryString['return'], true));

    if(!$this->Session->check('cadreguest01.plugin.start.returnUrl')) {
      $this->Session->write('cadreguest01.plugin.start.returnUrl', $queryString['return']);
    }

    $this->redirect($onFinish);
  }

//  /**
//   * Stop the flow when Other organization is POST'ed and redirect.
//   *
//   * @param array Array representing the petitionObject.
//   * @return none
//   */
//
//  protected function haltOnOtherAccessOrganization($petitionObject) {
//    // Set the petition to Denied and delete the token so that it
//    // cannot be used to access the form again, for example by
//    // hitting the back button.
//    $this->CoPetition->id = $petitionObject['CoPetition']['id'];
//    $this->CoPetition->saveField('status', PetitionStatusEnum::Denied);
//    $this->CoPetition->saveField('petitioner_token', null);
//
//    // Expunge the CO Person record.
//    $coPersonId = $petitionObject['CoPetition']['enrollee_co_person_id'];
//    $this->CoPetition->Co->CoPerson->expunge($coPersonId, 1);
//
//    // Prepare redirect for after logout to the form for
//    // requesting a new organization.
//    $this->Session->write('Logout.redirect', "https://support.access-ci.org/form/organization-request");
//
//    // Spoil the stored onFinish redirect.
//    $this->Session->consume('access02.plugin.petitionerAttributes.onFinish');
//
//    // Redirect to the /auth/logout handler that deletes the Auth part of
//    // the PHP session and then redirects to the Users controller with the
//    // logout action, which then causes the final redirect.
//    $this->redirect("/auth/logout");
//  }
//
//  /**
//   * Validate POST data from an add action.
//   *
//   * @return Array of validated data ready for saving or false if not validated.
//   */
//
//  private function validatePost() {
//    $data = $this->request->data;
//
//    // Validate the Access02Petition fields.
//    $petitionModel = new Access02Petition();
//    $petitionModel->clear();
//    $petitionData = array();
//    $petitionData['Access02Petition'] = $data['Access02Petition'];
//    $petitionModel->set($data);
//
//    $fields = array();
//    $fields[] = 'co_petition_id';
//    $fields[] = 'access_organization_id';
//
//    $args = array();
//    $args['fieldList'] = $fields;
//
//    if(!$petitionModel->validates($args)) {
//      $this->Flash->set(_txt('er.fields'), array('key' => 'error'));
//      return false;
//    }
//
//    return $data;
//  }
}
