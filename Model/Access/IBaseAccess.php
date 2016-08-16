<?php
interface IBaseAccess {
    function accessFind(array $query=[]);
    function accessSave(array $data=[]);
    function accessDelete($id);
}