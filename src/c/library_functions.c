/* Unit 7: Functions
 * Using library functions from math.h - no user-defined function yet,
 * just calling existing, pre-built library code. */
#include <stdio.h>
#include <math.h>

int main(void) {
    double x = 49.0;

    printf("sqrt(%.1f) = %.2f\n", x, sqrt(x));
    printf("pow(2, 10) = %.0f\n", pow(2, 10));
    printf("fabs(-7.5) = %.1f\n", fabs(-7.5));

    return 0;
}
