<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum Equipment: string
{
    case AbCrunchMachine = 'ab_crunch_machine';
    case AbWheel = 'ab_wheel';
    case AirBike = 'air_bike';
    case AssistedPullupMachine = 'assisted_pullup_machine';
    case BackExtensionMachine = 'back_extension_machine';
    case Barbell = 'barbell';
    case BattleRope = 'battle_rope';
    case BicepCurlMachine = 'bicep_curl_machine';
    case Bicycle = 'bicycle';
    case BosuBall = 'bosu_ball';
    case Cable = 'cable';
    case ChestFlyMachine = 'chest_fly_machine';
    case ChestPressMachine = 'chest_press_machine';
    case ClimbingRope = 'climbing_rope';
    case DipMachine = 'dip_machine';
    case DipStation = 'dip_station';
    case DonkeyCalfRaiseMachine = 'donkey_calf_raise_machine';
    case Dumbbell = 'dumbbell';
    case Elliptical = 'elliptical';
    case EzBar = 'ez_bar';
    case FlatBench = 'flat_bench';
    case GluteHamDeveloper = 'glute_ham_developer';
    case HackSquat = 'hack_squat';
    case HipAbductionMachine = 'hip_abduction_machine';
    case HipAdductionMachine = 'hip_adduction_machine';
    case HipThrustMachine = 'hip_thrust_machine';
    case JumpRope = 'jump_rope';
    case Kettlebell = 'kettlebell';
    case LatPulldownMachine = 'lat_pulldown_machine';
    case LegCurl = 'leg_curl';
    case LegExtension = 'leg_extension';
    case LegPress = 'leg_press';
    case LoopBand = 'loop_band';
    case MedicineBall = 'medicine_ball';
    case PecDeck = 'pec_deck';
    case PlateLoadedDipMachine = 'plate_loaded_dip_machine';
    case PlateLoadedLateralRaiseMachine = 'plate_loaded_lateral_raise_machine';
    case Plates = 'plates';
    case PlyoBox = 'plyo_box';
    case PreacherCurlMachine = 'preacher_curl_machine';
    case PullUpBar = 'pull_up_bar';
    case ResistanceBand = 'resistance_band';
    case ReverseFlyMachine = 'reverse_fly_machine';
    case Rings = 'rings';
    case Rower = 'rower';
    case Sandbag = 'sandbag';
    case SeatedCalfRaiseMachine = 'seated_calf_raise_machine';
    case SeatedRowMachine = 'seated_row_machine';
    case ShoulderPressMachine = 'shoulder_press_machine';
    case ShrugMachine = 'shrug_machine';
    case SkiErg = 'ski_erg';
    case SlamBall = 'slam_ball';
    case Sled = 'sled';
    case SmithMachine = 'smith_machine';
    case StabilityBall = 'stability_ball';
    case StairClimber = 'stair_climber';
    case StandingCalfRaiseMachine = 'standing_calf_raise_machine';
    case StationaryBike = 'stationary_bike';
    case SuspensionTrainer = 'suspension_trainer';
    case TrapBar = 'trap_bar';
    case Treadmill = 'treadmill';
    case TricepExtensionMachine = 'tricep_extension_machine';
    case WallBall = 'wall_ball';
    case WristRoller = 'wrist_roller';

    public function carriesLoad(): bool
    {
        return match ($this) {
            self::AbCrunchMachine,
            self::AssistedPullupMachine,
            self::BackExtensionMachine,
            self::Barbell,
            self::BicepCurlMachine,
            self::Cable,
            self::ChestFlyMachine,
            self::ChestPressMachine,
            self::DipMachine,
            self::DonkeyCalfRaiseMachine,
            self::Dumbbell,
            self::EzBar,
            self::HackSquat,
            self::HipAbductionMachine,
            self::HipAdductionMachine,
            self::HipThrustMachine,
            self::Kettlebell,
            self::LatPulldownMachine,
            self::LegCurl,
            self::LegExtension,
            self::LegPress,
            self::PecDeck,
            self::PlateLoadedDipMachine,
            self::PlateLoadedLateralRaiseMachine,
            self::Plates,
            self::PreacherCurlMachine,
            self::ReverseFlyMachine,
            self::Sandbag,
            self::SeatedCalfRaiseMachine,
            self::SeatedRowMachine,
            self::ShoulderPressMachine,
            self::ShrugMachine,
            self::Sled,
            self::SmithMachine,
            self::StandingCalfRaiseMachine,
            self::TrapBar,
            self::TricepExtensionMachine => true,
            default => false,
        };
    }
}
