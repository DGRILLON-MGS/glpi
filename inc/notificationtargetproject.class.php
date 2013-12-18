<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * NotificationTargetTicket Class
**/
class NotificationTargetProject extends NotificationTarget {

   /**
    *Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'               => __('New project'),
                      'update'            => __('Update of a project'),
                      'delete'            => __('Deletion of a project'));
      asort($events);
      return $events;
   }

   function getAdditionalTargets($event='') {
      $this->addTarget(Notification::MANAGER_USER,
                        __('Manager'));
      $this->addTarget(Notification::MANAGER_GROUP,
                        __('Manager group'));
      $this->addTarget(Notification::MANAGER_GROUP_SUPERVISOR,
                        __('Manager group supervisor'));
      $this->addTarget(Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR,
                        __('Manager group without supervisor'));
      $this->addTarget(Notification::TEAM_USER,
                        __('Project team user'));
      $this->addTarget(Notification::TEAM_GROUP,
                        __('Project team group'));
      $this->addTarget(Notification::TEAM_GROUP_SUPERVISOR,
                        __('Project team group supervisor'));
      $this->addTarget(Notification::TEAM_GROUP_WITHOUT_SUPERVISOR,
                        __('Project team group without supervisor'));
      $this->addTarget(Notification::TEAM_CONTACT,
                        __('Project team contact'));
      $this->addTarget(Notification::TEAM_SUPPLIER,
                        __('Project team supplier'));
   }
   
   function getSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::MANAGER_USER :
                  $this->getItemAuthorAddress();
                  break;

               //Send to the manager group of the project
               case Notification::MANAGER_GROUP :
                  $this->getItemGroupAddress();
                  break;

               //Send to the manager group supervisor of the project
               case Notification::MANAGER_GROUP_SUPERVISOR :
                  $this->getItemGroupSupervisorAddress();
                  break;

               //Send to the manager group without supervisor of the project
               case Notification::MANAGER_GROUP_WITHOUT_SUPERVISOR :
                  $this->getItemGroupWithoutSupervisorAddress();
                  break;
                  
               //Send to the users in project team
               case Notification::TEAM_USER :
                  $this->getTeamUsers();
                  break;
                  
               //Send to the groups in project team
               case Notification::TEAM_GROUP :
                  $this->getTeamGroups(0);
                  break;

               //Send to the group supervisors in project team
               case Notification::TEAM_GROUP_SUPERVISOR :
                  $this->getTeamGroups(1);
                  break;

               //Send to the groups without supervisors in project team
               case Notification::TEAM_GROUP_WITHOUT_SUPERVISOR :
                  $this->getTeamGroups(2);
                  break;

               //Send to the contact in project team
               case Notification::TEAM_CONTACT :
                  $this->getTeamContacts();
                  break;

                  //Send to the contact in project team
               case Notification::TEAM_SUPPLIER :
                  $this->getTeamSuppliers();
                  break;
                  
            }
         }
   }

   /**
    * Add team users to the notified user list
    *
   **/
   function getTeamUsers() {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'User'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $user = new User;
      foreach ($DB->request($query) as $data) {
         if ($user->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array('language' => $user->getField('language'),
                                            'users_id' => $user->getField('id')));
         }
      }
   }
   
   /**
    * Add team groups to the notified user list
    * @param $manager      0 all users, 1 only supervisors, 2 all users without supervisors
    *
   **/
   function getTeamGroups($manager) {
      global $DB;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Group'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      foreach ($DB->request($query) as $data) {
         $this->getAddressesByGroup($manager, $data['items_id']);
      }
   }

   /**
    * Add team contacts to the notified user list
    *
   **/
   function getTeamContacts() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Contact'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $contact = new Contact();
      foreach ($DB->request($query) as $data) {
         if ($contact->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $contact->fields["email"],
                                            "name"     => $contact->fields["name"]." ".$contact->fields["firstname"],
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }
   /**
    * Add team suppliers to the notified user list
    *
   **/
   function getTeamSuppliers() {
      global $DB, $CFG_GLPI;

      $query = "SELECT `items_id`
                FROM `glpi_projectteams`
                WHERE `glpi_projectteams`.`itemtype` = 'Supplier'
                     AND `glpi_projectteams`.`projects_id` = '".$this->obj->fields["id"]."'";
      $supplier = new Supplier();
      foreach ($DB->request($query) as $data) {
         if ($supplier->getFromDB($data['items_id'])) {
            $this->addToAddressesList(array("email"    => $supplier->fields["email"],
                                            "name"     => $supplier->fields["name"],
                                            "language" => $CFG_GLPI["language"],
                                            'usertype' => NotificationTarget::ANONYMOUS_USER));
         }
      }
   }

   /**
    * @see NotificationTarget::getDatasForTemplate()
   **/
   function getDatasForTemplate($event, $options=array()) {
      //----------- Reservation infos -------------- //
      $events                                  = $this->getAllEvents();

      $this->datas['##project.action##']   = $events[$event];
      $this->datas['##project.url##']      = $this->formatURL($options['additionnaloption']['usertype'],
                                              "Project_".$item->getField("id"));
      $datas["##project.name##"]           = $item->getField('name');
      $datas["##project.code##"]           = $item->getField('code');
      $datas["##project.description##"]    = $item->getField('content');
      $datas["##project.comments##"]       = $item->getField('comment');
      $datas["##project.creationdate##"]   = Html::convDateTime($item->getField('date'));
      $datas["##project.lastupdatedate##"] = Html::convDateTime($item->getField('date_mod'));
      $datas["##project.priority##"]       = CommonITILObject::getPriorityName($item->getField('priority'));
      $datas["##project.percent##"]        = Dropdown::getValueWithUnit($item->getField('percent_done'),"%");
      $datas["##project.planstartdate##"]  = Html::convDateTime($item->getField('plan_start_date'));
      $datas["##project.planenddate##"]    = Html::convDateTime($item->getField('plan_end_date'));
      $datas["##project.realstartdate##"]  = Html::convDateTime($item->getField('real_start_date'));
      $datas["##project.realenddate##"]    = Html::convDateTime($item->getField('real_end_date'));

      $datas["##project.plannedduration##"]   = Html::timestampToString(
                                                   ProjectTask::getTotalPlannedDurationForProject($item->getID()),
                                                   false);
      $datas["##project.effectiveduration##"] = Html::timestampToString(
                                                   ProjectTask::getTotalEffectiveDurationForProject($item->getID()),
                                                   false);


      $entity = new Entity();
      $datas["##project.entity##"] = '';
      $datas["##project.shortentity##"] = '';
      if ($entity->getFromDB($this->getEntity())) {
         $datas["##project.entity##"]      = $entity->getField('completename');
         $datas["##project.shortentity##"] = $entity->getField('name');
      }

      $datas["##project.father##"] = '';
      if ($item->getField('projects_id')) {
         $datas["##project.father##"]
                              = Dropdown::getDropdownName('glpi_projects',
                                                          $item->getField('projects_id'));
      }

      $datas["##project.state##"] = '';
      if ($item->getField('projectstates_id')) {
         $datas["##project.state##"]
                              = Dropdown::getDropdownName('glpi_projectstates',
                                                          $item->getField('projectstates_id'));
      }

      $datas["##project.type##"] = '';
      if ($item->getField('projecttypes_id')) {
         $datas["##project.type##"]
                              = Dropdown::getDropdownName('glpi_projecttypes',
                                                          $item->getField('projecttypes_id'));
      }

      $datas["##project.manager##"] = '';
      if ($item->getField('users_id')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id'));
         $datas["##project.manager##"] = $user_tmp->getName();
      }

      $datas["##project.managergroup##"] = '';
      if ($item->getField('groups_id')) {
         $datas["##project.managergroup##"]
                              = Dropdown::getDropdownName('glpi_groups',
                                                          $item->getField('groups_id'));
      }


      //Task infos
      $restrict = "`projects_id`='".$item->getField('id')."'";
      $restrict .= " ORDER BY `date` DESC, `id` ASC";

      $tasks          = getAllDatasFromTable('glpi_projecttasks',$restrict);
      $datas['tasks'] = array();
      foreach ($tasks as $task) {
         $tmp                            = array();
         $tmp['##task.creationdate##']   = Html::convDateTime($task['date']);
         $tmp['##task.lastupdatedate##'] = Html::convDateTime($task['date_mod']);
         $tmp['##task.name##']           = $task['name'];
         $tmp['##task.description##']    = $task['content'];
         $tmp['##task.comments##']       = $task['comment'];

         $tmp['##task.state##']          = Dropdown::getDropdownName('glpi_projectstates',
                                                                     $task['projectstates_id']);
         $tmp['##task.type##']           = Dropdown::getDropdownName('glpi_projecttasktypes',
                                                                     $task['projecttasktypes_id']);
         $tmp['##task.percent##']        = Dropdown::getValueWithUnit($task['percent_done'],"%");

         $datas["##task.planstartdate##"]  = '';
         $datas["##task.planenddate##"]    = '';
         $datas["##task.realstartdate##"]  = '';
         $datas["##task.realenddate##"]    = '';
         if (!is_null($task['plan_start_date'])) {
            $tmp['##task.planstartdate##']     = Html::convDateTime($task['plan_start_date']);
         }
         if (!is_null($task['plan_end_date'])) {
            $tmp['##task.planenddate##']     = Html::convDateTime($task['plan_end_date']);
         }
         if (!is_null($task['real_start_date'])) {
            $tmp['##task.realstartdate##']     = Html::convDateTime($task['real_start_date']);
         }
         if (!is_null($task['real_end_date'])) {
            $tmp['##task.realenddate##']     = Html::convDateTime($task['real_end_date']);
         }

         $datas['tasks'][]             = $tmp;
      }

      $datas["##project.numberoftasks##"] = count($datas['tasks']);

      //costs infos
      $restrict  = "`projects_id`='".$item->getField('id')."'";
      $restrict .= " ORDER BY `begin_date` DESC, `id` ASC";

      $costs          = getAllDatasFromTable('glpi_projectcosts',$restrict);
      $datas['costs'] = array();
      $datas["##project.totalcost##"] = 0;
      foreach ($costs as $cost) {
         $tmp = array();
         $tmp['##cost.name##']         = $cost['name'];
         $tmp['##cost.comment##']      = $cost['comment'];
         $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
         $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
         $tmp['##cost.cost##']         = Html::formatNumber($cost['cost']);
         $tmp['##cost.budget##']       = Dropdown::getDropdownName('glpi_budgets',
                                                                     $cost['budgets_id']);
         $datas["##project.totalcost##"] += $cost['cost'];
         $datas['costs'][]             = $tmp;

         /// TODO add ticket costs ?
      }
      $datas["##project.numberofcosts##"] = count($datas['costs']);

      $datas['log'] = array();
      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
         $tmp                               = array();
         $tmp["##project.log.date##"]    = $data['date_mod'];
         $tmp["##project.log.user##"]    = $data['user_name'];
         $tmp["##project.log.field##"]   = $data['field'];
         $tmp["##project.log.content##"] = $data['change'];
         $datas['log'][]                    = $tmp;
      }

      $datas["##project.numberoflogs##"] = count($datas['log']);

      $restrict         = "`projects_id`='".$item->getField('id')."'";
      $changes          = getAllDatasFromTable('glpi_changes_projects',$restrict);
      $datas['changes'] = array();
      if (count($changes)) {
         $change = new Change();
         foreach ($changes as $data) {
            if ($change->getFromDB($data['changes_id'])) {
               $tmp = array();
      $entity = new Entity();
      if ($entity->getFromDB($this->getEntity())) {
         $datas["##$objettype.entity##"]      = $entity->getField('completename');
         $datas["##$objettype.shortentity##"] = $entity->getField('name');
      }

               $tmp['##change.id##']
                              = $data['changes_id'];
               $tmp['##change.date##']
                              = $change->getField('date');
               $tmp['##change.title##']
                              = $change->getField('name');
               $tmp['##change.url##']
                              = $this->formatURL($options['additionnaloption']['usertype'],
                                                   "change_".$data['changes_id']);
               $tmp['##change.content##']
                              = $change->getField('content');

               $datas['changes'][] = $tmp;
            }
         }
      }

      $datas['##project.numberofchanges##'] = count($datas['changes']);


         // Document
         $query = "SELECT `glpi_documents`.*
                   FROM `glpi_documents`
                   LEFT JOIN `glpi_documents_items`
                     ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                   WHERE `glpi_documents_items`.`itemtype` =  'Project'
                         AND `glpi_documents_items`.`items_id` = '".$item->getField('id')."'";


         $datas["documents"] = array();
         if ($result = $DB->query($query)) {
            while ($data = $DB->fetch_assoc($result)) {
               $tmp                      = array();
               $tmp['##document.id##']   = $data['id'];
               $tmp['##document.name##'] = $data['name'];
               $tmp['##document.weblink##']
                                         = $data['link'];

               $tmp['##document.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                            "document_".$data['id']);
               $downloadurl              = "/front/document.send.php?docid=".$data['id'];

               $tmp['##document.downloadurl##']
                                         = $this->formatURL($options['additionnaloption']['usertype'],
                                                            $downloadurl);
               $tmp['##document.heading##']
                                         = Dropdown::getDropdownName('glpi_documentcategories',
                                                                     $data['documentcategories_id']);

               $tmp['##document.filename##']
                                         = $data['filename'];

               $datas['documents'][]     = $tmp;
            }
         }

         $datas["##project.urldocument##"]
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           $objettype."_".$item->getField("id").'_Document_Item$1');

         $datas["##project.numberofdocuments##"]
                        = count($datas['documents']);
      
      /// TODO Team
      /// TODO Items
      /// TODO contrats
      
      
      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = array('project.url'               => __('URL'),
                        'project.action'            => _n('Event', 'Events', 1),
                        'project.name'              => __('Name'),
                        'project.code'              => __('Code'),
                        'project.description'       => __('Description'),
                        'project.comments'          => __('Comments'),
                        'project.creationdate'      => __('Creation date'),
                        'project.lastupdatedate'    => __('Last update'),
                        'project.planstartdate'     => __('Planned start date'),
                        'project.planenddate'       => __('Planned end date'),
                        'project.realstartdate'     => __('Real start date'),
                        'project.realenddate'       => __('Real end date'),
                        'project.priority'          => __('Priority'),
                        'project.father'            => __('Father'),
                        'project.manager'           => __('Manager'),
                        'project.managergroup'      => __('Manager group'),
                        'project.type'              => __('Type'),
                        'project.state'             => _x('item', 'State'),
                        'project.percent'           => __('Percent done'),
                        'project.plannedduration'   => __('Planned duration'),
                        'project.effectiveduration' => __('Effective duration'),
                        'project.numberoftasks'     => _x('quantity', 'Number of tasks'),
                        'task.date'                 => __('Opening date'),
                        'task.name'                 => __('Name'),
                        'task.description'          => __('Description'),
                        'task.comments'             => __('Comments'),
                        'task.creationdate'         => __('Creation date'),
                        'task.lastupdatedate'       => __('Last update'),
                        'task.type'                 => __('Type'),
                        'task.state'                => _x('item', 'State'),
                        'task.percent'              => __('Percent done'),
                        'task.planstartdate'        => __('Planned start date'),
                        'task.planenddate'          => __('Planned end date'),
                        'task.realstartdate'        => __('Real start date'),
                        'task.realenddate'          => __('Real end date'),
                        'project.totalcost'             => __('Total cost'),
                        'project.numberofcosts'         => __('Number of costs'),
                        'project.numberoflogs'   => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                  _x('quantity', 'Number of items')),
                        'project.log.date'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                  __('Date')),
                        'project.log.user'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                  __('User')),
                        'project.log.field'      => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                  __('Field')),
                        'project.log.content'    => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                                  _x('name', 'Update')),
                        'project.numberofchanges'       => _x('quantity', 'Number of changes'),
                        'project.numberofdocuments'     => _x('quantity', 'Number of documents'),

                     );

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }


      //Tags without lang
      $tags = array('change.id'               => sprintf(__('%1$s: %2$s'), __('Change'), __('ID')),
                    'change.date'             => sprintf(__('%1$s: %2$s'), __('Change'), __('Date')),
                    'change.url'              => sprintf(__('%1$s: %2$s'), __('Change'), ('URL')),
                    'change.title'            => sprintf(__('%1$s: %2$s'), __('Change'),
                                                         __('Title')),
                    'change.content'          => sprintf(__('%1$s: %2$s'), __('Change'),
                                                         __('Description')),
                    'cost.name'               => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                     __('Name')),
                    'cost.comment'            => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                     __('Comments')),
                    'cost.datebegin'          => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                     __('Begin date')),
                    'cost.dateend'            => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                     __('End date')),
                    'cost.cost'               => __('Cost'),
                    'cost.budget'             => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                     __('Budget')),
                    'document.url'            => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('URL')),
                    'document.downloadurl'    => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Download URL')),
                    'document.heading'        => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Heading')),
                    'document.id'             => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('ID')),
                    'document.filename'       => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('File')),
                    'document.weblink'        => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Web Link')),
                    'document.name'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Name')),
                     'project.urldocument'   => sprintf(__('%1$s: %2$s'),
                                                            _n('Document', 'Documents', 2),
                                                            __('URL')),
                    'project.entity'         => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Complete name')),
                    'project.shortentity'    => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Name')),
                                                            
                     );


      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tags with just lang
      $tags = array('project.entity' => __('Entity'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }
      
      //Foreach global tags
      $tags = array('log'      => __('Historical'),
                    'tasks'    => _n('Task', 'Tasks', 2),
                    'costs'    => _n('Cost', 'Costs', 2),
                    'changes'  => _n('Change', 'Changes', 2));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }      
      asort($this->tag_descriptions);
   }
}
?>