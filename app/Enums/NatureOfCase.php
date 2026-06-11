<?php

namespace App\Enums;

enum NatureOfCase: string
{
    case ChildEndangermentExploitation = 'child_endangerment_exploitation';
    case FakeHackedProfile = 'fake_hacked_profile';
    case SexCrime = 'sex_crime';
    case AssaultHomicide = 'assault_homicide';
    case TerroristActivity = 'terrorist_activity';
    case GangActivity = 'gang_activity';
    case ThreatsStalking = 'threats_stalking';
    case CreditCardFraudTheft = 'credit_card_fraud_theft';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ChildEndangermentExploitation => 'Child Endangerment/Exploitation',
            self::FakeHackedProfile => 'Fake/Hacked Profile',
            self::SexCrime => 'Sex Crime',
            self::AssaultHomicide => 'Assault/Homicide',
            self::TerroristActivity => 'Terrorist Activity',
            self::GangActivity => 'Gang Activity',
            self::ThreatsStalking => 'Threats/Stalking',
            self::CreditCardFraudTheft => 'Credit Card Fraud or Theft',
            self::Other => 'Other',
        };
    }
}
