<?php
namespace App\Controller;

class AbstractDatabaseController extends AbstractController
{

    private function _getRows()
    {
        $token = $this->get('security.token_storage')->getToken();
        $rows = $this->databaseService->getDatabaseRows($token);
        return $rows;
    }

    protected function _getCategories()
    {
        $token = $this->get('security.token_storage')->getToken();

        $categories = $this->databaseService->getCategories($token);
        natcasesort($categories);
        return $categories;
    }

    protected function _saveDatabase($rows)
    {
        $token = $this->get('security.token_storage')->getToken();
        $this->databaseService->saveDatabaseRow($token, $rows);
    }

    protected function _addToDatabase($row)
    {
        $rows = $this->_getRows();
        $rows[] = $row;
        $this->_saveDatabase($rows);
    }

    protected function _updateDatabase($row)
    {
        $rows = $this->_getRows();
        for ($i = 0; $i < count($rows); $i++) {
            if ($rows[$i]->getId() == $row->getId()) {
                $rows[$i] = $row;
            }
        }
        $this->_saveDatabase($rows);
    }

    protected function _deleteRow($id)
    {
        $rows = $this->_getRows();
        for ($i = 0; $i < count($rows); $i++) {
            if ($rows[$i]->getId() == $id) {
                unset($rows[$i]);
            }
        }
        $rows = array_values($rows);
        $this->_saveDatabase($rows);
    }

    protected function _getRow($id)
    {
        $rows = $this->_getRows();
        foreach ($rows as $row) {
            if ($row->getId() == $id) {
                return $row;
            }
        }
        return null;
    }
}
