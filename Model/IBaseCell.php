<?php
App::uses('View','View');

interface IBaseCell {

    function display(View $view,$options=array());

}