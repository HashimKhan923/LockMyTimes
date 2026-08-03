import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as Location from 'expo-location';
import NetInfo from '@react-native-community/netinfo';
import { useEffect, useRef, useState } from 'react';
import { Image, Platform, Pressable, StyleSheet, Text, View } from 'react-native';
import { MotiView } from 'moti';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { extractErrorMessage } from '../../api/client';
import { clockIn, clockOut, fetchAttendanceIndex } from '../../api/endpoints/attendance';
import { Button } from '../../components/common/Button';
import { Screen } from '../../components/common/Screen';
import { elevatedShadow, radii, spacing, typography } from '../../theme/tokens';
import { useTheme } from '../../theme/useTheme';
import { sheetSpring } from '../../theme/motion';
import type { AttendanceStackParamList } from '../../navigation/AttendanceStack';
import type { LocationInfo } from '../../api/types';

type Props = NativeStackScreenProps<AttendanceStackParamList, 'ClockIn'>;

type Step = 'method' | 'selfie';

export function ClockInScreen({ route, navigation }: Props) {
  const { mode } = route.params;
  const theme = useTheme();
  const queryClient = useQueryClient();

  const [step, setStep] = useState<Step>(mode === 'out' ? 'selfie' : 'method');
  const [scanningQr, setScanningQr] = useState(false);
  const [selectedLocation, setSelectedLocation] = useState<LocationInfo | null>(null);
  const [qrToken, setQrToken] = useState<string | null>(null);
  const [coords, setCoords] = useState<{ lat: number; lng: number } | null>(null);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [selfie, setSelfie] = useState<{ uri: string; base64: string } | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const cameraRef = useRef<CameraView>(null);

  const { data: indexData } = useQuery({
    queryKey: ['attendance', 'index'],
    queryFn: () => fetchAttendanceIndex(),
    enabled: mode === 'in',
  });

  const hasAssignedLocations = (indexData?.assigned_locations?.length ?? 0) > 0;

  // Resolve GPS in the background as soon as the sheet opens — non-blocking,
  // never gates the rest of the flow (see build plan B6/B8).
  useEffect(() => {
    let cancelled = false;

    (async () => {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (cancelled) return;
      if (status !== 'granted') {
        setLocationError('Location permission denied — you can still continue without it.');
        return;
      }
      try {
        const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        if (!cancelled) setCoords({ lat: pos.coords.latitude, lng: pos.coords.longitude });
      } catch {
        if (!cancelled) setLocationError('Could not get your location — you can still continue without it.');
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const submitMutation = useMutation({
    mutationFn: async () => {
      const netState = await NetInfo.fetch();
      if (netState.isConnected === false) {
        throw new Error('OFFLINE');
      }

      const selfieDataUrl = selfie ? `data:image/jpeg;base64,${selfie.base64}` : undefined;

      if (mode === 'in') {
        return clockIn({
          source: qrToken ? 'qr' : 'mobile',
          location_id: qrToken ? undefined : selectedLocation?.id,
          qr_token: qrToken ?? undefined,
          lat: coords?.lat,
          lng: coords?.lng,
          selfie: selfieDataUrl,
        });
      }
      return clockOut({
        source: 'mobile',
        lat: coords?.lat,
        lng: coords?.lng,
        selfie: selfieDataUrl,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance'] });
      navigation.navigate('Success', { message: mode === 'in' ? 'Clocked in' : 'Clocked out' });
    },
    onError: (err: unknown) => {
      setSubmitError(
        err instanceof Error && err.message === 'OFFLINE'
          ? "You're offline — check your connection and try again."
          : extractErrorMessage(err)
      );
    },
  });

  async function handleCapture() {
    if (!cameraRef.current) return;
    const photo = await cameraRef.current.takePictureAsync({ base64: true, quality: 0.5 });
    if (photo?.base64) {
      setSelfie({ uri: photo.uri, base64: photo.base64 });
    }
  }

  function handleBarcodeScanned(result: { data: string }) {
    if (!result.data) return;
    setQrToken(result.data);
    setSelectedLocation(null);
    setScanningQr(false);
    setStep('selfie');
  }

  return (
    <Screen>
      <Text style={[typography.heading, { color: theme.text, marginTop: spacing.md }]}>
        {mode === 'in' ? 'Clock in' : 'Clock out'}
      </Text>

      {locationError && (
        <Text style={[typography.caption, { color: theme.warning, marginTop: spacing.xs }]}>
          {locationError}
        </Text>
      )}
      {!locationError && !coords && (
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
          Locating…
        </Text>
      )}

      {step === 'method' && (
        <MotiView
          from={{ opacity: 0, translateY: 16 }}
          animate={{ opacity: 1, translateY: 0 }}
          transition={sheetSpring}
          style={styles.section}
        >
          {!scanningQr ? (
            <>
              {hasAssignedLocations ? (
                <>
                  <Text style={[typography.subheading, { color: theme.text }]}>Choose a location</Text>
                  {(indexData?.assigned_locations ?? []).map((loc) => (
                    <Pressable
                      key={loc.id}
                      onPress={() => {
                        setSelectedLocation(loc);
                        setQrToken(null);
                      }}
                      style={[
                        styles.locationCard,
                        {
                          borderColor: selectedLocation?.id === loc.id ? theme.primary : 'transparent',
                          backgroundColor: theme.surface,
                        },
                        Platform.OS === 'ios' ? elevatedShadow.ios : elevatedShadow.android,
                      ]}
                    >
                      <Text style={[typography.body, { color: theme.text }]}>{loc.name}</Text>
                      {loc.is_headquarters && (
                        <Text style={[typography.caption, { color: theme.textMuted }]}>Headquarters</Text>
                      )}
                    </Pressable>
                  ))}

                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
                    No QR code at your location? No problem — just pick it above and continue. Scanning is only
                    needed if you'd rather use a shared QR code instead.
                  </Text>

                  <Pressable onPress={() => setScanningQr(true)} style={styles.qrLink}>
                    <Text style={[typography.body, { color: theme.primary }]}>Scan a QR code instead</Text>
                  </Pressable>
                </>
              ) : (
                <View style={[styles.noLocationCard, { backgroundColor: theme.surfaceAlt }]}>
                  <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
                    No location assigned to your account
                  </Text>
                  <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
                    You can check in directly — just tap Continue below.
                  </Text>
                </View>
              )}

              <View style={{ marginTop: spacing.lg }}>
                <Button
                  title="Continue"
                  onPress={() => setStep('selfie')}
                  disabled={hasAssignedLocations && !selectedLocation}
                />
              </View>
            </>
          ) : (
            <View style={styles.cameraWrap}>
              {cameraPermission?.granted ? (
                <CameraView
                  style={StyleSheet.absoluteFill}
                  facing="back"
                  barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
                  onBarcodeScanned={handleBarcodeScanned}
                />
              ) : (
                <Button title="Allow camera access" onPress={() => requestCameraPermission()} />
              )}
              <Pressable onPress={() => setScanningQr(false)} style={styles.cancelScan}>
                <Text style={[typography.body, { color: theme.onPrimary }]}>Cancel</Text>
              </Pressable>
            </View>
          )}
        </MotiView>
      )}

      {step === 'selfie' && (
        <MotiView
          from={{ opacity: 0, translateY: 16 }}
          animate={{ opacity: 1, translateY: 0 }}
          transition={sheetSpring}
          style={styles.section}
        >
          <Text style={[typography.subheading, { color: theme.text }]}>Take a quick selfie</Text>

          {!selfie ? (
            <View style={styles.cameraWrap}>
              {cameraPermission?.granted ? (
                <CameraView ref={cameraRef} style={StyleSheet.absoluteFill} facing="front" />
              ) : (
                <Button title="Allow camera access" onPress={() => requestCameraPermission()} />
              )}
            </View>
          ) : (
            <Image source={{ uri: selfie.uri }} style={styles.preview} />
          )}

          <View style={{ marginTop: spacing.md, gap: spacing.sm }}>
            {!selfie ? (
              <Button title="Capture" onPress={handleCapture} disabled={!cameraPermission?.granted} />
            ) : (
              <>
                <Button
                  title={mode === 'in' ? 'Confirm clock-in' : 'Confirm clock-out'}
                  onPress={() => submitMutation.mutate()}
                  loading={submitMutation.isPending}
                />
                <Button title="Retake" variant="secondary" onPress={() => setSelfie(null)} />
              </>
            )}
          </View>

          {submitError && (
            <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>
              {submitError}
            </Text>
          )}
        </MotiView>
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { marginTop: spacing.lg },
  locationCard: {
    borderWidth: 2,
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  noLocationCard: {
    borderRadius: radii.lg,
    padding: spacing.md,
    marginTop: spacing.sm,
  },
  qrLink: { marginTop: spacing.md, alignItems: 'center' },
  cameraWrap: {
    height: 280,
    borderRadius: radii.lg,
    overflow: 'hidden',
    marginTop: spacing.md,
    backgroundColor: '#000',
  },
  cancelScan: {
    position: 'absolute',
    bottom: spacing.md,
    alignSelf: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radii.pill,
  },
  preview: {
    height: 280,
    borderRadius: radii.lg,
    marginTop: spacing.md,
  },
});
