<?php

namespace App\Enums;

enum LegalProcess: string
{
    case CourtOrderOutsideUs = 'court_order_outside_us';
    case Emergency = 'emergency';
    case Subpoena = 'subpoena';
    case CourtOrderDomesticUs = 'court_order_domestic_us';
    case SearchWarrantDomesticUs = 'search_warrant_domestic_us';
    case PenRegisterTrapTrace = 'pen_register_trap_trace';
    case Mlat = 'mlat';
    case ProductionOrder = 'production_order';
    case DecretoOrdine = 'decreto_ordine';
    case Auskunftsersuchen = 'auskunftsersuchen';
    case RequisitionJudiciaire = 'requisition_judiciaire';
    case Section58Request = 'section_58_request';
    case Authorisation = 'authorisation';
    case PrivacyActRequest = 'privacy_act_request';
    case Section91Request = 'section_91_request';
    case RipaGrade1 = 'ripa_grade_1';
    case RipaGrade2 = 'ripa_grade_2';
    case RipaGrade3 = 'ripa_grade_3';

    public function label(): string
    {
        return match ($this) {
            self::CourtOrderOutsideUs => 'Court Order/Request (Outside US)',
            self::Emergency => 'Emergency',
            self::Subpoena => 'Subpoena',
            self::CourtOrderDomesticUs => 'Court Order (Domestic US)',
            self::SearchWarrantDomesticUs => 'Search Warrant (Domestic US)',
            self::PenRegisterTrapTrace => 'Pen Register/Trap and Trace and Title III',
            self::Mlat => 'MLAT',
            self::ProductionOrder => 'Production Order',
            self::DecretoOrdine => 'Decreto/Ordine',
            self::Auskunftsersuchen => 'Auskunftsersuchen',
            self::RequisitionJudiciaire => 'Requisition judiciaire',
            self::Section58Request => 'Section 58 Request',
            self::Authorisation => 'Authorisation',
            self::PrivacyActRequest => 'Privacy Act Request',
            self::Section91Request => 'Section 91 Request',
            self::RipaGrade1 => 'RIPA – Grade 1',
            self::RipaGrade2 => 'RIPA – Grade 2',
            self::RipaGrade3 => 'RIPA – Grade 3',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CourtOrderOutsideUs => 'Court order or request originating outside the United States',
            self::Emergency => 'Emergency disclosure request involving imminent risk of harm',
            self::Subpoena => 'Legal subpoena compelling production of records',
            self::CourtOrderDomesticUs => 'Court order issued within the United States',
            self::SearchWarrantDomesticUs => 'Search warrant issued by a US court',
            self::PenRegisterTrapTrace => 'Pen register, trap and trace, or Title III wiretap order',
            self::Mlat => 'Mutual Legal Assistance Treaty request',
            self::ProductionOrder => 'Court-issued production order for records',
            self::DecretoOrdine => 'Italian court decree or order (Decreto/Ordine)',
            self::Auskunftsersuchen => 'German information request (Auskunftsersuchen)',
            self::RequisitionJudiciaire => 'French judicial requisition (Réquisition judiciaire)',
            self::Section58Request => 'Request under Section 58 of applicable legislation',
            self::Authorisation => 'Formal authorisation under applicable legislation',
            self::PrivacyActRequest => 'Request under the Privacy Act',
            self::Section91Request => 'Request under Section 91 of applicable legislation',
            self::RipaGrade1 => 'RIPA Grade 1 authorisation (UK)',
            self::RipaGrade2 => 'RIPA Grade 2 authorisation (UK)',
            self::RipaGrade3 => 'RIPA Grade 3 authorisation (UK)',
        };
    }

    public function isWarrant(): bool
    {
        return in_array($this, [
            self::SearchWarrantDomesticUs,
            self::PenRegisterTrapTrace,
        ]);
    }
}
