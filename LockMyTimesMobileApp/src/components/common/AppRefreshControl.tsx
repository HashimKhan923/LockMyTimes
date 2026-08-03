import { RefreshControl, type RefreshControlProps } from 'react-native';
import { useTheme } from '../../theme/useTheme';

/**
 * Shared pull-to-refresh indicator (see build plan B7) — same tinting
 * everywhere instead of every list screen repeating the same three props.
 */
export function AppRefreshControl(props: Omit<RefreshControlProps, 'tintColor' | 'colors'>) {
  const theme = useTheme();

  return <RefreshControl tintColor={theme.primary} colors={[theme.primary]} {...props} />;
}
