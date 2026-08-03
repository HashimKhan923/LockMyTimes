import { useColorScheme } from 'react-native';
import { categorical, colors, gradients } from './tokens';

export function useTheme() {
  const scheme = useColorScheme() === 'dark' ? 'dark' : 'light';
  return {
    ...colors[scheme],
    gradients: gradients[scheme],
    categorical: categorical[scheme],
  };
}
