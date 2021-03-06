<?php
// $Id: rdreference.class.php 869 2011-12-22 08:50:24Z i.bitcero $
// --------------------------------------------------------------
// RapidDocs
// Documentation system for Xoops.
// Author: Eduardo Cortés <i.bitcero@gmail.com>
// Email: i.bitcero@gmail.com
// License: GPL 2.0
// --------------------------------------------------------------

class RDReference extends RMObject
{
    public function __construct($id = null)
    {
        $this->db =  XoopsDatabaseFactory::getDatabaseConnection();
        $this->_dbtable = $this->db->prefix('mod_docs_references');
        $this->setNew();
        $this->initVarsFromTable();

        if (null === $id) {
            return;
        }

        if (is_numeric($id)) {
            if (!$this->loadValues($id)) {
                return;
            }
            $this->unsetNew();
        }
    }

    public function id()
    {
        return $this->getVar('id_ref');
    }

    public function save()
    {
        if ($this->isNew()) {
            return $this->saveToTable();
        }

        return $this->updateTable();
    }

    public function delete()
    {
        return $this->deleteFromTable();
    }
}
