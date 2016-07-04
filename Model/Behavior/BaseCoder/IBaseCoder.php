<?php
interface IBaseCoder {
    function encode($value,$data=[]);
    function decode($value,$data=[]);
}