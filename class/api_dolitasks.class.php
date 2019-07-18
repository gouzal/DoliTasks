<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

dol_include_once('/dolitasks/class/llx_dolitasks_my_tasks.class.php');



/**
 * \file    dolitasks/class/api_dolitasks.class.php
 * \ingroup dolitasks
 * \brief   File for API management of llx_dolitasks_my_tasks.
 */

/**
 * API class for dolitasks llx_dolitasks_my_tasks
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DoliTasksApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'name',
    );


    /**
     * @var llx_dolitasks_my_tasks $llx_dolitasks_my_tasks {@type llx_dolitasks_my_tasks}
     */
    public $llx_dolitasks_my_tasks;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->llx_dolitasks_my_tasks = new llx_dolitasks_my_tasks($this->db);
    }

    /**
     * Get properties of a llx_dolitasks_my_tasks object
     *
     * Return an array with llx_dolitasks_my_tasks informations
     *
     * @param 	int 	$id ID of llx_dolitasks_my_tasks
     * @return 	array|mixed data without useless information
	 *
     * @url	GET llx_dolitasks_my_taskss/{id}
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->llx_dolitasks_my_tasks->read) {
			throw new RestException(401);
		}

        $result = $this->llx_dolitasks_my_tasks->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'llx_dolitasks_my_tasks not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('llx_dolitasks_my_tasks',$this->llx_dolitasks_my_tasks->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->llx_dolitasks_my_tasks);
    }


    /**
     * List llx_dolitasks_my_taskss
     *
     * Get a list of llx_dolitasks_my_taskss
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
     * @throws RestException
     *
     * @url	GET /llx_dolitasks_my_taskss/
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $restictonsocid = 0;	// Set to 1 if there is a field socid in table of object

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if ($restictonsocid && ! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."llx_dolitasks_my_tasks_mytable as t";

        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= " WHERE 1 = 1";

        // Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $tmpobject = new llx_dolitasks_my_tasks($db);
        if ($tmpobject->ismultientitymanaged) $sql.= ' AND t.entity IN ('.getEntity('llx_dolitasks_my_tasks').')';
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($restictonsocid && $socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($restictonsocid && $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($restictonsocid && $search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $llx_dolitasks_my_tasks_static = new llx_dolitasks_my_tasks($db);
                if($llx_dolitasks_my_tasks_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($llx_dolitasks_my_tasks_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve llx_dolitasks_my_tasks list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No llx_dolitasks_my_tasks found');
        }
		return $obj_ret;
    }

    /**
     * Create llx_dolitasks_my_tasks object
     *
     * @param array $request_data   Request datas
     * @return int  ID of llx_dolitasks_my_tasks
     *
     * @url	POST llx_dolitasks_my_taskss/
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->llx_dolitasks_my_tasks->create) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->llx_dolitasks_my_tasks->$field = $value;
        }
        if( ! $this->llx_dolitasks_my_tasks->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->llx_dolitasks_my_tasks->id;
    }

    /**
     * Update llx_dolitasks_my_tasks
     *
     * @param int   $id             Id of llx_dolitasks_my_tasks to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT llx_dolitasks_my_taskss/{id}
     */
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->llx_dolitasks_my_tasks->create) {
            throw new RestException(401);
        }

        $result = $this->llx_dolitasks_my_tasks->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'llx_dolitasks_my_tasks not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('llx_dolitasks_my_tasks',$this->llx_dolitasks_my_tasks->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->llx_dolitasks_my_tasks->$field = $value;
        }

        if($this->llx_dolitasks_my_tasks->update($id, DolibarrApiAccess::$user))
            return $this->get($id);

        return false;
    }

    /**
     * Delete llx_dolitasks_my_tasks
     *
     * @param   int     $id   llx_dolitasks_my_tasks ID
     * @return  array
     *
     * @url	DELETE llx_dolitasks_my_tasks/{id}
     */
    function delete($id)
    {
    	if(! DolibarrApiAccess::$user->rights->llx_dolitasks_my_tasks->delete) {
			throw new RestException(401);
		}
        $result = $this->llx_dolitasks_my_tasks->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'llx_dolitasks_my_tasks not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('llx_dolitasks_my_tasks',$this->llx_dolitasks_my_tasks->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

		if( !$this->llx_dolitasks_my_tasks->delete(DolibarrApiAccess::$user, 0))
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'llx_dolitasks_my_tasks deleted'
            )
        );
    }


    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
    	$object = parent::_cleanObjectDatas($object);

    	/*unset($object->note);
    	unset($object->address);
    	unset($object->barcode_type);
    	unset($object->barcode_type_code);
    	unset($object->barcode_type_label);
    	unset($object->barcode_type_coder);*/

    	return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $llx_dolitasks_my_tasks = array();
        foreach (llx_dolitasks_my_tasksApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $llx_dolitasks_my_tasks[$field] = $data[$field];
        }
        return $llx_dolitasks_my_tasks;
    }
}
