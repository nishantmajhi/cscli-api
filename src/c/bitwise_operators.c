/* Unit 4: Operators and Expression
 * Bitwise operators: &, |, ^, ~, <<, >> */
#include <stdio.h>

int main(void) {
    unsigned int a = 12, b = 10; /* 1100, 1010 */

    printf("a & b  = %u\n", a & b);
    printf("a | b  = %u\n", a | b);
    printf("a ^ b  = %u\n", a ^ b);
    printf("~a     = %u\n", ~a);
    printf("a << 2 = %u\n", a << 2);
    printf("a >> 2 = %u\n", a >> 2);

    return 0;
}
