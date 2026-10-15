/* Unit 5: Control Statement
 * The exit() function, and break/continue inside loops. */
#include <stdio.h>
#include <stdlib.h>

int main(void) {
    printf("Numbers 1-10, skipping multiples of 3, stopping at 8:\n");

    for (int i = 1; i <= 10; i++) {
        if (i % 3 == 0) {
            continue; /* skip this iteration */
        }
        if (i == 8) {
            break; /* stop the loop entirely */
        }
        printf("%d\n", i);
    }

    if (1 == 0) {
        exit(1); /* demonstrates exit(), never reached */
    }

    printf("Done.\n");
    return 0;
}
