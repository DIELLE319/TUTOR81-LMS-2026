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

// permette di selezionare solo i piani attivi o quelli non attivi
$suspended = isset( $_GET['suspended'] ) ?
        filter_input(INPUT_GET, 'suspended', FILTER_VALIDATE_BOOLEAN) ? '1' : '0' :
    '0';

Editor::inst( $db, 'company_plans' )
    ->field( 
        Field::inst( 'company_plans.id' )
            ->set( false ),
        Field::inst( 'companies.id' )
            ->set( false ),
        Field::inst( 'companies.is_tutor' )
            ->set( false ),
        Field::inst( 'company_plans.company_id' )
            ->options( Options::inst()
                ->table( 'companies' )
                ->value( 'companies.id' )
                ->label( 'companies.business_name' )
                ->where( function ($q) {
                    $q->where( 'companies.deleted', '0', '=' );
                })
                ->order('companies.business_name')
            )
            ->validator( Validate::dbValues() ),
        Field::inst( 'company_plans.plan_id' )
            ->options( Options::inst()
                ->table( 'plans' )
                ->value( 'id' )
                ->label( 'short_desc_plan' )
                ->where( function ($q) {
                    $q->where( 'active', '1', '=' );
                })
                ->order('for_tutor,plan_price')
            )
            ->validator( Validate::dbValues() ),
        Field::inst( 'company_plans.validity_start' )
            ->validator( Validate::dateFormat(
                "Y-m-d",
                ValidateOptions::inst()
                    ->allowEmpty( false )
            ) )
            ->getFormatter( Format::dateSqlToFormat( "Y-m-d" ) )
            ->setFormatter( Format::dateFormatToSql( "Y-m-d" ) ),
        Field::inst( 'company_plans.validity_end' )
            ->validator( Validate::dateFormat(
                "Y-m-d",
                ValidateOptions::inst()
                    ->allowEmpty( false )
            ) )
            ->getFormatter( Format::dateSqlToFormat( "Y-m-d" ) )
            ->setFormatter( Format::dateFormatToSql( "Y-m-d" ) ),
        Field::inst( 'company_plans.discount' )
            ->validator( Validate::minMaxNum(
                0, 100, ",", ValidateOptions::inst()
                    ->message( 'Sconto percentuale minimo 0, massimo 100' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire la percentuale di sconto (minimo 0, massimo 100)' )
            ) ),
        Field::inst( 'company_plans.ecommerce' )
            ->validator( Validate::boolean()),
        Field::inst( 'company_plans.customized_courses' )
            ->validator( Validate::boolean()),
        Field::inst( 'company_plans.max_admin' )
            ->validator( Validate::minMaxNum(
                0, 999, ",", ValidateOptions::inst()
                    ->message( 'Numero di amministatori minimo 0, massimo 999' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire il numero di amministratori (minimo 0, massimo 999)' )
            ) ),
        Field::inst( 'company_plans.max_concurrent_users' )
            ->validator( Validate::minNum(
                0, ",", ValidateOptions::inst()
                    ->message( 'Inserire un numero positivo (0 per nessun limite)' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire un numero positivo (0 per nessun limite)' )
            ) ),
        Field::inst( 'company_plans.suspended' )
            ->validator( Validate::boolean()),
        Field::inst( 'company_plans.price' )
            ->validator( Validate::minNum(
                0, ",", ValidateOptions::inst()
                    ->message( 'Prezzo minimo 0 Euro' ) 
            ) )
            ->validator( Validate::notEmpty( ValidateOptions::inst()
                ->message( 'Inserire il prezzo del piano' )
            ) ),
        Field::inst( 'company_plans.invoiced' )
            ->validator( Validate::boolean()),
        Field::inst( 'companies.business_name' )
            ->set( false ),
        Field::inst( 'plans.short_desc_plan' )
            ->set( false )
    )
    ->leftJoin( 'companies', 'companies.id', '=', 'company_plans.company_id' )
    ->leftJoin( 'plans', 'plans.id', '=', 'company_plans.plan_id' )
    ->where('companies.deleted','0')
    ->where('company_plans.suspended', $suspended)
    ->process($_POST)
    ->json();