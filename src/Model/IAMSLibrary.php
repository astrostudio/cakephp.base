<?php
interface IAMSLibary {
    function search($category,$subcategory,$tags=[]);
    function download($category,$subcategory,$name);
}