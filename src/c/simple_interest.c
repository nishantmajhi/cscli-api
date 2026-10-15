/* Unit 1: Problem Solving with Computer
 * Worked example of the analyse -> code -> compile -> run cycle:
 * compute simple interest from principal, rate, and time. */
#include <stdio.h>

int main(void) {
    float principal, rate, time, interest;

    printf("Enter principal, rate (%%), and time (years): ");
    scanf("%f %f %f", &principal, &rate, &time);

    interest = (principal * rate * time) / 100;

    printf("Simple Interest = %.2f\n", interest);
    return 0;
}
