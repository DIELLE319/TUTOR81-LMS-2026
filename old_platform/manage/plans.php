<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/license.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
include( "../lib/DataTables/DataTables.php" );

// Alias Editor classes so they are easy to use
use
    DataTables\Editor,
    DataTables\Editor\Field,
    DataTables\Editor\Format,
    DataTables\Editor\Mjoin,
    DataTables\Editor\Options,
    DataTables\Editor\Upload,
    DataTables\Editor\Validate,
    DataTables\Editor\ValidateOptions;

Editor::inst( $db, 'plans' )
    ->field( 
        Field::inst( 'plans.id' )
            ->set( false ),
        Field::inst( 'plans.short_desc_plan' )
            ->validator(
                Validate::minMaxLen(3, 64), 
                ValidateOptions::inst()
                    ->message( 'Inserire una descrizione di lunghezza compresa tra 3 e 64 caratteri' )
            ),
        Field::inst( 'plans.long_desc_plan' ) 
            ->validator(
                Validate::minMaxLen(0, 512),
                ValidateOptions::inst()
                    ->message( 'Inserire una descrizione di lunghezza compresa tra 3 e 64 caratteri' )
            ),
        Field::inst( 'plans.no_expiration' )
            ->validator( Validate::boolean()),
        Field::inst( 'plans.for_tutor' )
            ->validator( Validate::boolean()),
        Field::inst( 'plans.plan_price' )
            ->validator( Validate::minNum(
                0, ",", ValidateOptions::inst()
                    ->message( 'Prezzo minimo 0 Euro' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire il prezzo del piano' )
            ) ),
        Field::inst( 'plans.discount' )
            ->validator( Validate::minMaxNum(
                0, 100, ",", ValidateOptions::inst()
                    ->message( 'Sconto percentuale minimo 0, massimo 100' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire la percentuale di sconto (minimo 0, massimo 100)' )
            ) ),
        Field::inst( 'plans.ecommerce' )
            ->validator( Validate::boolean()),
        Field::inst( 'plans.customized_courses' )
            ->validator( Validate::boolean()),
        Field::inst( 'plans.max_admin' )
            ->validator( Validate::minMaxNum(
                0, 999, ",", ValidateOptions::inst()
                    ->message( 'Numero di amministatori minimo 0, massimo 999' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire il numero di amministratori (minimo 0, massimo 999)' )
            ) ),
        Field::inst( 'plans.max_concurrent_users' )
            ->validator( Validate::minNum(
                0, ",", ValidateOptions::inst()
                    ->message( 'Inserire un numero positivo (0 per nessun limite)' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire un numero positivo (0 per nessun limite)' )
            ) ),
        Field::inst( 'plans.active' )
            ->validator( Validate::boolean())
    )
    ->process($_POST)
    ->json();