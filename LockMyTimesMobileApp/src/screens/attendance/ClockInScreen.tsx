import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as Location from 'expo-location';
import NetInfo from '@react-native-community/netinfo';
import { useEffect, useState } from 'react';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';
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

export function ClockInScreen({ route, navigation }: Props) {
  const { mode } = route.params;
  const theme = useTheme();
  const queryClient = useQueryClient();

  const [scanningQr, setScanningQr] = useState(false);
  const [selectedLocation, setSelectedLocation] = useState<LocationInfo | null>(null);
  const [coords, setCoords] = useState<{ lat: number; lng: number } | null>(null);
  const [locationError, setLocationError] = useState<string | null>(null);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const [cameraPermission, requestCameraPermission] = useCameraPermissions();

  const { data: indexData } = useQuery({
    queryKey: ['attendance', 'index'],
    queryFn: () => fetchAttendanceIndex(),
    enabled: mode === 'in',
  });

  const isRemote = indexData?.employment_mode === 'remote';
  // Remote employees keep a primary location on file for reporting/timezone
  // purposes only (see Employee::skipsGeofence()) — it must never drive the
  // clock-in UI (no location card, no QR requirement, no camera).
  const assignedLocations = isRemote ? [] : (indexData?.assigned_locations ?? []);
  const hasAssignedLocations = assignedLocations.length > 0;
  const needsLocationPicker = assignedLocations.length > 1;

  // A single assigned location needs no picker at all — select it automatically
  // so the employee can clock in with one tap.
  useEffect(() => {
    if (assignedLocations.length === 1) setSelectedLocation(assignedLocations[0]);
  }, [assignedLocations.length === 1 ? assignedLocations[0]?.id : null]);

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
    mutationFn: async (vars: { qrToken?: string; locationId?: number }) => {
      const netState = await NetInfo.fetch();
      if (netState.isConnected === false) {
        throw new Error('OFFLINE');
      }

      if (mode === 'in') {
        return clockIn({
          source: vars.qrToken ? 'qr' : 'mobile',
          location_id: vars.qrToken ? undefined : vars.locationId,
          qr_token: vars.qrToken,
          lat: coords?.lat,
          lng: coords?.lng,
        });
      }
      return clockOut({
        source: 'mobile',
        lat: coords?.lat,
        lng: coords?.lng,
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

  function handleBarcodeScanned(result: { data: string }) {
    if (!result.data) return;
    setScanningQr(false);
    submitMutation.mutate({ qrToken: result.data });
  }

  function handleClockInPress() {
    if (selectedLocation?.requires_qr) {
      setScanningQr(true);
      return;
    }
    submitMutation.mutate({ locationId: selectedLocation?.id });
  }

  return (
    <Screen>
      <Text style={[typography.heading, { color: theme.text, marginTop: spacing.md }]}>
        {mode === 'in' ? 'Clock in' : 'Clock out'}
      </Text>

      {locationError && !isRemote && (
        <Text style={[typography.caption, { color: theme.warning, marginTop: spacing.xs }]}>
          {locationError}
        </Text>
      )}
      {!locationError && !coords && !isRemote && (
        <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
          Locating…
        </Text>
      )}
      {isRemote && mode === 'in' && (
        <View style={[styles.remoteBanner, { backgroundColor: theme.primaryMuted, marginTop: spacing.sm }]}>
          <Text style={[typography.caption, { color: theme.primary, fontWeight: '700' }]}>
            You're set to Remote — clock in from anywhere, no location check applies.
          </Text>
        </View>
      )}

      {mode === 'in' ? (
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
                  {needsLocationPicker ? (
                    <>
                      <Text style={[typography.subheading, { color: theme.text }]}>Choose a location</Text>
                      {assignedLocations.map((loc) => (
                        <Pressable
                          key={loc.id}
                          onPress={() => setSelectedLocation(loc)}
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
                    </>
                  ) : (
                    <View style={[styles.noLocationCard, { backgroundColor: theme.surfaceAlt }]}>
                      <Text style={[typography.body, { color: theme.text, fontWeight: '600' }]}>
                        {selectedLocation?.name}
                      </Text>
                      <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
                        Your assigned location — just tap Clock in below.
                      </Text>
                    </View>
                  )}

                  {selectedLocation?.requires_qr && (
                    <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.md }]}>
                      This location requires a QR code scan — tapping Clock in will open your camera.
                    </Text>
                  )}
                </>
              ) : isRemote ? null : (
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
                  title={selectedLocation?.requires_qr ? 'Clock in (scan QR)' : 'Clock in now'}
                  onPress={handleClockInPress}
                  disabled={hasAssignedLocations && !selectedLocation}
                  loading={submitMutation.isPending}
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

          {submitError && (
            <Text style={[typography.caption, { color: theme.danger, marginTop: spacing.sm }]}>
              {submitError}
            </Text>
          )}
        </MotiView>
      ) : (
        <MotiView
          from={{ opacity: 0, translateY: 16 }}
          animate={{ opacity: 1, translateY: 0 }}
          transition={sheetSpring}
          style={styles.section}
        >
          <Text style={[typography.subheading, { color: theme.text }]}>Confirm clock-out</Text>
          <Text style={[typography.caption, { color: theme.textMuted, marginTop: spacing.xs }]}>
            You're about to clock out for today.
          </Text>

          <View style={{ marginTop: spacing.lg }}>
            <Button
              title="Confirm clock-out"
              onPress={() => submitMutation.mutate({})}
              loading={submitMutation.isPending}
            />
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
  remoteBanner: { borderRadius: radii.md, padding: spacing.sm, paddingHorizontal: spacing.md },
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
});
