<?php

// This COmanage Registry enrollment plugin is intended to be used
// with an anonymous enrollment flow for the CADRE Guest CO. At the end of
// the flow the user is required to set a password for their
// new CADRE Guest ID.
//
// The following enrollment steps are implemented:
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
//     password for their new CADRE Guest ID and then
//     redirects back to the URL in the return query
//     parameter saved from the start step.
//
// start:
//   - Records the return query parameter if present,
//     later used in the provision step.

App::uses('CoPetitionsController', 'Controller');
App::uses('Cadre', 'CadreAuthenticator.Model');
App::uses('CadreAuthenticator', 'CadreAuthenticator.Model');
 
class Cadreguest01EnrollerCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "Cadreguest01EnrollerCoPetitions";

  public $uses = array(
    "CoPetition",
    "Cadreguest01Enroller.Cadreguest01Enroller"
  );

  /**
   * Plugin functionality following petitionerAttributes step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
   
  protected function execute_plugin_petitionerAttributes($id, $onFinish) {
    // Pull the petition.
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['EnrolleeCoPerson'][] = 'EmailAddress';
    $args['contain']['EnrolleeCoPerson']['CoOrgIdentityLink']['OrgIdentity'] = 'EmailAddress';

    $petition = $this->CoPetition->find('first', $args);
    $this->log("Petitioner Attributes: Petition is " . print_r($petition, true));

    $coId = $petition['CoPetition']['co_id'];
    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];
    $coPersonRoleId = $petition['CoPetition']['enrollee_co_person_role_id'];
    $orgIdentityId = $petition['CoPetition']['enrollee_org_identity_id'];

    // Pull the plugin configuration.
    $args = array();
    $args['conditions']['Cadreguest01Enroller.co_enrollment_flow_wedge_id'] = $this->params['named']['efwid'];
    $args['contain'] = false;

    $pluginCfg = $this->Cadreguest01Enroller->find('first', $args);
    if(empty($pluginCfg)) {
      throw new RuntimeException(_txt('pl.cadreguest01_enroller.cfg.notfound'));
    }

    // Process incoming POST data when input email has been rejected
    // and user has submitted again.
    if($this->request->is('post')) {
      // Update the email address on the CO Person record and OrgID.
      $petitionEmail = $this->request->data['email'];

      $opt = array();
      $opt['provision'] = false;
      $this->CoPetition
           ->EnrolleeCoPerson
           ->EmailAddress
           ->id = $petition['EnrolleeCoPerson']['EmailAddress'][0]['id'];
      $this->CoPetition
           ->EnrolleeCoPerson
           ->EmailAddress
           ->saveField('mail', $petitionEmail, $opt);
      $this->CoPetition
           ->EnrolleeCoPerson
           ->CoOrgIdentityLink
           ->OrgIdentity
           ->EmailAddress
           ->id = $petition['EnrolleeCoPerson']['CoOrgIdentityLink'][0]['OrgIdentity']['EmailAddress'][0]['id'];
      $this->CoPetition
           ->EnrolleeCoPerson
           ->CoOrgIdentityLink
           ->OrgIdentity
           ->EmailAddress
           ->saveField('mail', $petitionEmail, $opt);
    } else {
      $petitionEmail = $petition['EnrolleeCoPerson']
                                ['CoOrgIdentityLink']
                                [0]
                                ['OrgIdentity']
                                ['EmailAddress']
                                [0]
                                ['mail'];
    }

    // Before continuing check the email address the anonymous user entered into
    // the form to see if this person was already registered and if so
    // return back into the flow.

    $args = array();
    $args['conditions']['EmailAddress.mail'] = $petitionEmail;
    $args['contain'] = 'CoPerson';

    $emailAddress = $this->CoPetition->EnrolleeCoPerson->EmailAddress->find('all', $args);

    if($emailAddress) {
      // Loop over the EmailAddress and exclude those attached
      // to the current petition and those associated with CO Person
      // that does not have Active status.
      foreach($emailAddress as $e) {
        if($e['EmailAddress']['co_person_id'] != $coPersonId &&$e['EmailAddress']['org_identity_id'] != $orgIdentityId) {
          if($e['CoPerson']['status'] == StatusEnum::Active && $e['CoPerson']['co_id'] == $coId) {
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

            // Set the status on the petition to Duplicate.
            $this->CoPetition->id = $id;
            $this->CoPetition->saveField('status', PetitionStatusEnum::Duplicate, $opt);

            if($this->Session->check('cadreguest01.plugin.start.returnUrl')) {
              // Read the return URL into CILogon from the session.
              $url = $this->Session->consume('cadreguest01.plugin.start.returnUrl');

              // Append the idphint query parameter.
              $idp = $pluginCfg['Cadreguest01Enroller']['idp'];
              $url = $url . "&idphint=" . urlencode($idp);

              // Redirect back to CILogon.
              $this->redirect("$url");
            } else {
              // We don't have the URL back into the flow so just redirect to
              // the project's homepage.
              $this->redirect("https://cadre5safes.org.au/");
            }
          }
        }
      }
    }

    // Reject emails from restricted domains.
    if(empty($pluginCfg['Cadreguest01Enroller']['domains'])) {
      // No restricted domains so just go on.
      $this->redirect($onFinish);
    }

    $domainString = $pluginCfg['Cadreguest01Enroller']['domains'];
    $restrictedDomains = explode(',', $domainString);

    $petitionEmailDomain = explode('@', $petitionEmail)[1];

    if(!in_array($petitionEmailDomain, $restrictedDomains)) {
      // Email is not from a restricted domain so continue with flow.
      $this->redirect($onFinish);
    }

    // Email is from a restricted domain so fall through to the view
    // to render form and collect a different email address.

    $this->log("Cadreguest01Enroller rejecting email $petitionEmail with domain $petitionEmailDomain");
      
    // Set the flash.
    $this->Flash->set(_txt('pl.cadreguest01_enroller.domains.notpermitted', array($petitionEmailDomain)), array('key' => 'error'));

    // Set the CoPetition ID to use as a hidden form element.
    $this->set('vv_coPetitionId', $id);

    // Set the enrollment flow wedge ID to use as a hidden form element.
    $this->set('vv_efwid', $this->params['named']['efwid']);

    // Set the petitioner token to use as a hidden form element.
    $this->set('vv_token', $petition['CoPetition']['petitioner_token']);

    // Save the onFinish URL to which we must redirect after receiving
    // the incoming POST data.
    if(!$this->Session->check('cadreguest01enroller.plugin.petitionerAttributes.onFinish')) {
      $this->Session->write('cadreguest01enroller.plugin.petitionerAttributes.onFinish', $onFinish);
    }

  }

  /**
   * Plugin functionality following provision step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
   
  protected function execute_plugin_provision($id, $onFinish) {
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['EnrolleeCoPerson'][] = 'EmailAddress';

    $petition = $this->CoPetition->find('first', $args);
    $this->log("Provision: Petition is " . print_r($petition, true));

    $coId = $petition['CoPetition']['co_id'];
    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];

    // Find the Email Address.
    $email = $petition['EnrolleeCoPerson']['EmailAddress'][0]['mail'];

    // We assume that the CO has one and only one instantiated CadreAuthenticator
    // plugin and it is used for CADRE Guestjpassword management.
    $args = array();
    $args['conditions']['Authenticator.co_id'] = $coId;
    $args['conditions']['Authenticator.plugin'] = 'CadreAuthenticator';
    $args['contain'] = false;

    $authenticator = $this->CoPetition->Co->Authenticator->find('first', $args);

    $args = array();
    $args['conditions']['CadreAuthenticator.authenticator_id'] = $authenticator['Authenticator']['id'];
    $args['contain'] = false;

    $cadreAuthenticatorModel = new CadreAuthenticator();

    $cadreAuthenticator = $cadreAuthenticatorModel->find('first', $args);

    $cfg = array();
    $cfg['Authenticator'] = $authenticator['Authenticator'];
    $cfg['CadreAuthenticator'] = $cadreAuthenticator['CadreAuthenticator'];
    $cadreAuthenticatorModel->setConfig($cfg);

    // Set the CoPetition ID to use as a hidden form element.
    $this->set('co_petition_id', $id);

    $this->set('vv_authenticator', $cadreAuthenticator);
    $this->set('vv_co_person_id', $coPersonId);
    $this->set('vv_email', $email);

    // Process incoming POST data.
    if($this->request->is('post')) {
      try {
        // Set the password.
        $cadreAuthenticatorModel->manage($this->data, $coPersonId, true);

        // Display the view explaining that the process is completed
        // and will be redirected back into the flow and asked to
        // authenticate.

        // Pull this plugin configuration to obtain the SAML IdP entityID.
        $args = array();
        $args['conditions']['Cadreguest01Enroller.co_enrollment_flow_wedge_id'] = $this->params['named']['efwid'];
        $args['contain'] = false;

        $pluginCfg = $this->Cadreguest01Enroller->find('first', $args);
        if(empty($pluginCfg)) {
          throw new RuntimeException(_txt('pl.cadreguest01_enroller.cfg.notfound'));
        }

        $entityID = $pluginCfg['Cadreguest01Enroller']['idp'];

        $returnUrl = $this->Session->consume('cadreguest01.plugin.start.returnUrl');
        $returnUrl = $returnUrl . "&idphint=" . urlencode($entityID);

        $this->set('vv_return_url', $returnUrl);
        $this->render('Cadreguest01Enroller.Cadreguest01EnrollerCoPetitions/done');
      } catch (Exception $e) {
        // Fall through to display the form again.
        $this->set('vv_efwid', $this->data['Cadre']['co_enrollment_flow_wedge_id']);
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
    } // POST
    
    // GET fall through to view.
  }

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
}
