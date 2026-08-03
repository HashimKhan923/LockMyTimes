import {
  ArrowLeft,
  Bell,
  Briefcase,
  Buildings,
  Cake,
  CalendarBlank,
  Camera,
  CaretRight,
  Check,
  CheckCircle,
  CheckSquare,
  ChatCircleDots,
  Circle,
  Clock,
  DotsThreeVertical,
  Envelope,
  FileText,
  Gear,
  GenderIntersex,
  House,
  LockKey,
  Megaphone,
  Money,
  PaperPlaneTilt,
  Paperclip,
  Plus,
  Receipt,
  SignOut,
  Square,
  SquaresFour,
  Trash,
  User,
  Users,
  UsersThree,
  X,
  type IconProps,
  type Icon as PhosphorIcon,
} from 'phosphor-react-native';

/**
 * Central icon set (see build plan — Nocturne redesign pass). Every screen
 * renders icons through this one component instead of importing Ionicons
 * directly, so the whole app's icon language (Phosphor, consistent weight)
 * stays in one place. `name` accepts the app's existing Ionicons-style keys
 * so screens didn't need their icon strings rewritten — just the import.
 */
const MAP: Record<string, PhosphorIcon> = {
  home: House,
  time: Clock,
  calendar: CalendarBlank,
  'calendar-outline': CalendarBlank,
  checkbox: CheckSquare,
  'checkbox-outline': CheckSquare,
  'square-outline': Square,
  grid: SquaresFour,
  ellipse: Circle,
  'notifications-outline': Bell,
  checkmark: Check,
  'checkmark-done-outline': CheckCircle,
  camera: Camera,
  'lock-closed-outline': LockKey,
  'chevron-forward': CaretRight,
  'people-outline': Users,
  'people-circle-outline': UsersThree,
  'person-outline': User,
  'log-out-outline': SignOut,
  'trash-outline': Trash,
  add: Plus,
  'document-attach-outline': Paperclip,
  send: PaperPlaneTilt,
  'document-text-outline': FileText,
  'receipt-outline': Receipt,
  'cash-outline': Money,
  'megaphone-outline': Megaphone,
  'settings-outline': Gear,
  'arrow-back': ArrowLeft,
  'chatbubble-outline': ChatCircleDots,
  'ellipsis-vertical': DotsThreeVertical,
  close: X,
  mail: Envelope,
  cake: Cake,
  'gender-outline': GenderIntersex,
  briefcase: Briefcase,
  business: Buildings,
};

export interface IconComponentProps extends Omit<IconProps, 'weight'> {
  name: string;
  weight?: IconProps['weight'];
}

export function Icon({ name, weight = 'regular', ...rest }: IconComponentProps) {
  const Cmp = MAP[name] ?? Circle;
  return <Cmp weight={weight} {...rest} />;
}