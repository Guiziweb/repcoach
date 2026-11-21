<?php

declare(strict_types=1);

namespace App\Exercise\Enum;

enum Muscle: string
{
    case Abductors = 'abductors';
    case Adductors = 'adductors';
    case AnteriorDeltoid = 'anterior_deltoid';
    case BicepsBrachii = 'biceps_brachii';
    case Brachialis = 'brachialis';
    case Brachioradialis = 'brachioradialis';
    case ErectorSpinae = 'erector_spinae';
    case ForearmExtensors = 'forearm_extensors';
    case ForearmFlexors = 'forearm_flexors';
    case Forearms = 'forearms';
    case Gastrocnemius = 'gastrocnemius';
    case GluteusMaximus = 'gluteus_maximus';
    case GluteusMedius = 'gluteus_medius';
    case Hamstrings = 'hamstrings';
    case HipFlexors = 'hip_flexors';
    case LateralDeltoid = 'lateral_deltoid';
    case LatissimusDorsi = 'latissimus_dorsi';
    case Obliques = 'obliques';
    case PectoralisMajor = 'pectoralis_major';
    case PosteriorDeltoid = 'posterior_deltoid';
    case QuadratusLumborum = 'quadratus_lumborum';
    case Quadriceps = 'quadriceps';
    case RectusAbdominis = 'rectus_abdominis';
    case Rhomboids = 'rhomboids';
    case SerratusAnterior = 'serratus_anterior';
    case Soleus = 'soleus';
    case Supraspinatus = 'supraspinatus';
    case TibialisAnterior = 'tibialis_anterior';
    case TransverseAbdominis = 'transverse_abdominis';
    case Trapezius = 'trapezius';
    case TricepsBrachii = 'triceps_brachii';
}
