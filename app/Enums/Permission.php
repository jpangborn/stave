<?php

namespace App\Enums;

enum Permission: string
{
    case LOGIN = 'login';
    case VIEW_PROFILE = 'view profile';
    case EDIT_PROFILE = 'edit profile';
    case DELETE_PROFILE = 'delete profile';
    case VIEW_PEOPLE = 'view people';
    case CREATE_PEOPLE = 'create people';
    case EDIT_PEOPLE = 'edit people';
    case DELETE_PEOPLE = 'delete people';
    case VIEW_SONGS = 'view songs';
    case CREATE_SONGS = 'create songs';
    case EDIT_SONGS = 'edit songs';
    case DELETE_SONGS = 'delete songs';
    case VIEW_RECORDINGS = 'view recordings';
    case CREATE_RECORDINGS = 'create recordings';
    case EDIT_RECORDINGS = 'edit recordings';
    case DELETE_RECORDINGS = 'delete recordings';
    case VIEW_SHEETS = 'view sheets';
    case CREATE_SHEETS = 'create sheets';
    case EDIT_SHEETS = 'edit sheets';
    case DELETE_SHEETS = 'delete sheets';
    case VIEW_GROUPS = 'view groups';
    case CREATE_GROUPS = 'create groups';
    case EDIT_GROUPS = 'edit groups';
    case DELETE_GROUPS = 'delete groups';
    case VIEW_ENROLLMENTS = 'view enrollments';
    case CREATE_ENROLLMENTS = 'create enrollments';
    case EDIT_ENROLLMENTS = 'edit enrollments';
    case DELETE_ENROLLMENTS = 'delete enrollments';
}
